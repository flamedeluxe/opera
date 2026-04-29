<?php

/**
 * Скачивает страницу экскурсии с uuopera.ru, парсит контент и записывает/обновляет элемент инфоблока.
 *
 * Перед первым запуском: php local/tools/uuopera_excursions_iblock_install.php
 *
 * CLI:
 *   php local/tools/uuopera_excursions_import_uuopera.php
 *   php local/tools/uuopera_excursions_import_uuopera.php --url=https://uuopera.ru/afisha/excursions/jekskursija-puteshestvie-po-teatru/
 *   php local/tools/uuopera_excursions_import_uuopera.php --code=jekskursija-puteshestvie-po-teatru
 *
 * Браузер (админ): /local/tools/uuopera_excursions_import_uuopera.php
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
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_excursion_parse_uuopera.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_excursions_iblock_bootstrap.php';

$defaultUrl = 'https://uuopera.ru/afisha/excursions/jekskursija-puteshestvie-po-teatru/';
$url = $defaultUrl;
$code = '';

if (PHP_SAPI === 'cli') {
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--url=')) {
            $url = substr($arg, 6);
        } elseif (str_starts_with($arg, '--code=')) {
            $code = substr($arg, 7);
        }
    }
} elseif (isset($_GET['url']) && is_string($_GET['url']) && $_GET['url'] !== '') {
    $url = $_GET['url'];
}
if (isset($_GET['code']) && is_string($_GET['code']) && $_GET['code'] !== '') {
    $code = $_GET['code'];
}

if ($code === '') {
    $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
    $code = basename(rtrim($path, '/'));
}
if ($code === '' || $code === '.' || $code === '..') {
    echo "Не удалось определить символьный код элемента (укажите --code=...).\n";
    exit(1);
}

try {
    $html = uuopera_excursion_fetch_remote_html($url);
    $d = uuopera_excursion_parse_html_from_uuopera($html);
} catch (Throwable $e) {
    echo 'Ошибка загрузки/разбора: ' . $e->getMessage() . "\n";
    exit(1);
}

if (trim($d['name']) === '') {
    echo "Парсер не извлёк заголовок — проверьте разметку страницы или URL.\n";
    exit(1);
}

try {
    $struct = uuopera_excursions_bootstrap_structure();
} catch (Throwable $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

$bid = $struct['iblock_id'];
$sectionId = $struct['section_id'];

$sessionsJson = json_encode($d['sessions'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

$scalarProps = [
    'PRICE_VALUE' => $d['price_value'],
    'DURATION_HERO' => $d['duration_hero'],
    'RADARIO_AFISHA_KEY' => $d['radario_afisha_key'],
    'SESSIONS_JSON' => $sessionsJson,
    'SLIDER_ID' => $d['slider_id'],
    'FOOTER_DURATION' => $d['footer_duration'],
    'FOOTER_PRICE' => $d['footer_price'],
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
    'NAME' => $d['name'],
    'CODE' => $code,
    'ACTIVE' => 'Y',
    'DETAIL_TEXT' => $d['body_html'],
    'DETAIL_TEXT_TYPE' => 'html',
    'IBLOCK_SECTION_ID' => $sectionId,
];

$heroUrl = trim((string) ($d['hero_image'] ?? ''));
if ($heroUrl !== '') {
    $heroFa = CFile::MakeFileArray($heroUrl);
    if (is_array($heroFa) && !empty($heroFa['tmp_name']) && is_file($heroFa['tmp_name'])) {
        $heroFa['MODULE_ID'] = 'iblock';
        $fields['PREVIEW_PICTURE'] = $heroFa;
    } else {
        echo "Предупреждение: обложка по URL не скачалась (проверьте сеть/SSL), поле «Анонс» не обновлено.\n";
    }
}

if ($existing) {
    $id = (int) $existing['ID'];
    $ok = $el->Update($id, $fields);
    if (!$ok) {
        global $APPLICATION;
        echo 'Ошибка Update: ' . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown') . "\n";
        exit(1);
    }
} else {
    $fields['IBLOCK_ID'] = $bid;
    $id = (int) $el->Add($fields);
    if ($id <= 0) {
        global $APPLICATION;
        echo 'Ошибка Add: ' . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown') . "\n";
        exit(1);
    }
}

CIBlockElement::SetPropertyValuesEx($id, $bid, $scalarProps);
$galProp = uuopera_iblock_gallery_property_from_urls(is_array($d['gallery'] ?? null) ? $d['gallery'] : []);
CIBlockElement::SetPropertyValuesEx($id, $bid, ['GALLERY' => $galProp !== [] ? $galProp : false]);

echo "Импорт выполнен. IBLOCK_ID={$bid}, ELEMENT_ID={$id}, CODE={$code}\n";
echo "Источник: {$url}\n";
echo "Админка: Контент → Информационные блоки → uuopera.ru → Экскурсии (афиша).\n";
