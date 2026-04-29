<?php

/**
 * Массовый импорт карточек афиши с uuopera.ru в инфоблок uuopera_afisha_events.
 *
 * CLI: php local/tools/uuopera_afisha_bulk_import_uuopera.php
 * Опции: --sleep=1 (секунды между запросами)
 */

declare(strict_types=1);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "DOCUMENT_ROOT not found\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_BUFFER_USED', true);
define('BX_NO_ACCELERATOR_RESET', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (PHP_SAPI !== 'cli') {
    global $USER;
    if (!is_object($USER) || !$USER->IsAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Только для администратора.';
        exit;
    }
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    echo "Модуль iblock не установлен.\n";
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_iblock_gallery.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_parse_uuopera.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events_bootstrap.php';

$sleepSec = 1.0;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--sleep=([\d.]+)$/', $arg, $mm)) {
        $sleepSec = max(0.0, (float) $mm[1]);
    }
}

$urls = [
    'https://uuopera.ru/afisha/excursions/jekskursija-puteshestvie-po-teatru/',
    'https://uuopera.ru/afisha/ballet/krasavitsa-angara/',
    'https://uuopera.ru/afisha/opera/evgenij-onegin/',
    'https://uuopera.ru/afisha/ballet/tysjacha-i-odna-noch/',
    'https://uuopera.ru/afisha/no-category/jubilejnyj-vecher-darimy-linhovoin/',
    'https://uuopera.ru/afisha/ballet/djujmovochka/',
    'https://uuopera.ru/afisha/concert/pashalnyj-koncert/',
    'https://uuopera.ru/afisha/opera/svadba-figaro/',
    'https://uuopera.ru/afisha/opera/bal-maskarad/',
    'https://uuopera.ru/afisha/no-category/koncert-posvjashhennyj-k-85-letiju-anatolija-andreeva/',
    'https://uuopera.ru/afisha/ballet/shopeniana-i-karmina-burana/',
    'https://uuopera.ru/afisha/opera/karmen/',
    'https://uuopera.ru/afisha/ballet/lebedinoe-ozero/',
    'https://uuopera.ru/afisha/no-category/alye-parusa/',
    'https://uuopera.ru/afisha/no-category/koncert-urok-muzhestva/',
    'https://uuopera.ru/afisha/no-category/koncert-ko-dnju-pobedy/',
    'https://uuopera.ru/afisha/no-category/premera-baleta-babochka/',
    'https://uuopera.ru/afisha/no-category/muha-cokotuha/',
    'https://uuopera.ru/afisha/no-category/sojuz-kompozitorov-toonto/',
    'https://uuopera.ru/afisha/opera/bogema/',
    'https://uuopera.ru/afisha/ballet/anjuta/',
    'https://uuopera.ru/afisha/no-category/gala-koncert-zvjozd-opery-i-baleta/',
    'https://uuopera.ru/afisha/opera/balzhan-khatan/',
    'https://uuopera.ru/afisha/ballet/balet-na-bajkale-krasavica-angara/',
    'https://uuopera.ru/afisha/concert/gala-koncert-balet-na-bajkale-burjatija-2025/',
    'https://uuopera.ru/afisha/opera/jerejehjen/',
    'https://uuopera.ru/afisha/ballet/zhizel/',
    'https://uuopera.ru/afisha/no-category/premera-opery-traviata/',
    'https://uuopera.ru/afisha/ballet/shhelkunchik/',
];

try {
    $struct = uuopera_afisha_events_bootstrap_iblock();
} catch (Throwable $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

$bid = $struct['iblock_id'];
$sectionTitles = [
    'opera' => 'Опера',
    'ballet' => 'Балет',
    'concert' => 'Концерты',
    'excursions' => 'Экскурсии',
    'no-category' => 'Без категории',
    'festivals' => 'Фестивали',
    'online' => 'Онлайн',
    'performances' => 'Представления',
];

$errors = 0;
$ok = 0;
$n = 0;
foreach ($urls as $url) {
    $n++;
    $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
    if (!preg_match('#/afisha/([^/]+)/([^/]+)/?$#', $path, $pm)) {
        echo "[{$n}] SKIP bad URL: {$url}\n";
        $errors++;
        continue;
    }
    $category = $pm[1];
    $code = $pm[2];
    $sectionName = $sectionTitles[$category] ?? $category;

    try {
        $html = uuopera_excursion_fetch_remote_html($url);
        $payload = uuopera_afisha_parse_uuopera_page($html, $category);
    } catch (Throwable $e) {
        echo "[{$n}] FAIL {$code}: " . $e->getMessage() . "\n";
        $errors++;
        if ($sleepSec > 0) {
            usleep((int) ($sleepSec * 1_000_000));
        }
        continue;
    }

    if (trim((string) ($payload['name'] ?? '')) === '') {
        echo "[{$n}] FAIL {$code}: нет заголовка\n";
        $errors++;
        if ($sleepSec > 0) {
            usleep((int) ($sleepSec * 1_000_000));
        }
        continue;
    }

    $sid = uuopera_afisha_events_ensure_section($bid, $category, $sectionName);
    if ($sid <= 0) {
        echo "[{$n}] FAIL {$code}: раздел\n";
        $errors++;
        continue;
    }

    $sessionsJson = json_encode($payload['sessions'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    $scalar = [
        'CATEGORY' => $category,
        'LAYOUT' => (string) ($payload['layout'] ?? 'event'),
        'AGE' => (string) ($payload['age'] ?? ''),
        'HERO_META_HTML' => (string) ($payload['hero_meta_html'] ?? ''),
        'RADARIO_HERO_MODE' => (string) ($payload['radario_hero_mode'] ?? ''),
        'RADARIO_AFISHA_KEY' => (string) ($payload['radario_afisha_key'] ?? ''),
        'RADARIO_HERO_EVENT_ID' => (int) ($payload['radario_hero_event_id'] ?? 0) > 0
            ? (string) (int) $payload['radario_hero_event_id']
            : '',
        'SESSIONS_JSON' => $sessionsJson,
        'PARTICIPANTS_HTML' => (string) ($payload['participants_html'] ?? ''),
        'SLIDER_ID' => (string) ($payload['slider_id'] ?? ''),
        'FOOTER_DURATION' => (string) ($payload['footer_duration'] ?? ''),
        'FOOTER_PRICE' => (string) ($payload['footer_price'] ?? ''),
        'SOURCE_URL' => $url,
    ];

    $elRes = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $bid, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID']
    );
    $existing = $elRes->Fetch();
    $el = new CIBlockElement();

    $fields = [
        'NAME' => $payload['name'],
        'CODE' => $code,
        'ACTIVE' => 'Y',
        'IBLOCK_SECTION_ID' => $sid,
        'DETAIL_TEXT' => (string) ($payload['description_html'] ?? ''),
        'DETAIL_TEXT_TYPE' => 'html',
    ];

    $heroUrl = trim((string) ($payload['hero_image'] ?? ''));
    if ($heroUrl !== '') {
        $heroFa = CFile::MakeFileArray($heroUrl);
        if (is_array($heroFa) && !empty($heroFa['tmp_name']) && is_file($heroFa['tmp_name'])) {
            $heroFa['MODULE_ID'] = 'iblock';
            $fields['PREVIEW_PICTURE'] = $heroFa;
        }
    }

    if ($existing) {
        $id = (int) $existing['ID'];
        if (!$el->Update($id, $fields)) {
            global $APPLICATION;
            echo "[{$n}] FAIL Update {$code}: " . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : '?') . "\n";
            $errors++;
        } else {
            CIBlockElement::SetPropertyValuesEx($id, $bid, $scalar);
            $galUrls = is_array($payload['gallery'] ?? null) ? $payload['gallery'] : [];
            $galProp = uuopera_iblock_gallery_property_from_urls($galUrls);
            CIBlockElement::SetPropertyValuesEx($id, $bid, ['GALLERY' => $galProp !== [] ? $galProp : false]);
            echo "[{$n}] OK update {$code} (ID={$id})\n";
            $ok++;
        }
    } else {
        $fields['IBLOCK_ID'] = $bid;
        $id = (int) $el->Add($fields);
        if ($id <= 0) {
            global $APPLICATION;
            echo "[{$n}] FAIL Add {$code}: " . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : '?') . "\n";
            $errors++;
        } else {
            CIBlockElement::SetPropertyValuesEx($id, $bid, $scalar);
            $galUrls = is_array($payload['gallery'] ?? null) ? $payload['gallery'] : [];
            $galProp = uuopera_iblock_gallery_property_from_urls($galUrls);
            CIBlockElement::SetPropertyValuesEx($id, $bid, ['GALLERY' => $galProp !== [] ? $galProp : false]);
            echo "[{$n}] OK add {$code} (ID={$id})\n";
            $ok++;
        }
    }

    if ($sleepSec > 0) {
        usleep((int) ($sleepSec * 1_000_000));
    }
}

echo "\nГотово: успешно {$ok}, ошибок {$errors}. IBLOCK_ID={$bid}\n";
