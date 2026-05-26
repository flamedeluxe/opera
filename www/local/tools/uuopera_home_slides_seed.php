<?php
/**
 * Заполняет ИБ «Главная: слайды» четырьмя событиями из афиши.
 * Запуск: php local/tools/uuopera_home_slides_seed.php [--dry-run]
 */
if (!defined('B_PROLOG_INCLUDED')) {
    define('B_PROLOG_INCLUDED', true);
}
$_SERVER['DOCUMENT_ROOT'] = '/var/www/bitrix';
require_once '/var/www/bitrix/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

Loader::includeModule('iblock');

$dryRun = in_array('--dry-run', $argv ?? [], true);

// ── 1. ID целевого ИБ ──────────────────────────────────────────────────────
$targetIblockId = (int) Option::get('uuopera', 'cms_home_slides_iblock_id', '0');
if ($targetIblockId <= 0) {
    echo "ERROR: cms_home_slides_iblock_id не задан. Запустите uuopera_cms_iblocks_install.php\n";
    exit(1);
}
echo "Целевой ИБ «Главная: слайды» ID={$targetIblockId}\n\n";

// ── 2. Проверяем, есть ли уже элементы ────────────────────────────────────
$existingCnt = (int) CIBlockElement::GetList([], ['IBLOCK_ID' => $targetIblockId], []);
if ($existingCnt > 0) {
    echo "ИБ уже содержит {$existingCnt} элемент(ов). Повторный запуск добавит дубли.\n";
    echo "Удалите существующие элементы вручную или запустите с --force.\n";
    if (!in_array('--force', $argv ?? [], true)) {
        exit(0);
    }
}

// ── 3. Выбираем события из афиши (ИБ 3) с картинкой ──────────────────────
$afishaIblockId = (int) Option::get('uuopera', 'afisha_events_iblock_id', '3');

// Берём события без категории excursions, с preview_picture, активные
$res = CIBlockElement::GetList(
    ['DATE_ACTIVE_FROM' => 'DESC', 'ID' => 'DESC'],
    [
        'IBLOCK_ID'       => $afishaIblockId,
        'ACTIVE'          => 'Y',
        '!PREVIEW_PICTURE' => false,
    ],
    false,
    ['nTopCount' => 40],
    ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE']
);

$candidates = [];
while ($ob = $res->GetNextElement()) {
    $f = $ob->GetFields();
    $p = $ob->GetProperties();

    $cat = trim((string) ($p['CATEGORY']['VALUE'] ?? ''));
    if ($cat === 'excursions') {
        continue;
    }

    $picId = (int) ($f['PREVIEW_PICTURE'] ?? 0);
    if ($picId <= 0) {
        continue;
    }

    $picPath = CFile::GetPath($picId);
    if (empty($picPath)) {
        continue;
    }

    $age     = trim((string) ($p['AGE']['VALUE'] ?? ''));
    $radKey  = trim((string) ($p['RADARIO_AFISHA_KEY']['VALUE'] ?? ''));
    $metaRaw = $p['HERO_META_HTML']['VALUE'] ?? '';
    $meta    = is_array($metaRaw) ? (string) ($metaRaw['TEXT'] ?? '') : (string) $metaRaw;

    $code = trim((string) ($f['CODE'] ?? ''));
    $linkUrl = $code !== '' ? "/afisha/{$code}/" : '/afisha/';

    $candidates[] = [
        'name'               => trim((string) ($f['NAME'] ?? '')),
        'link_url'           => $linkUrl,
        'subtext_html'       => $meta,
        'age_mark'           => $age,
        'radario_afisha_key' => $radKey,
        'pic_id'             => $picId,
        'pic_path'           => $picPath,
    ];

    if (count($candidates) >= 4) {
        break;
    }
}

if (empty($candidates)) {
    echo "Нет подходящих событий в афише (с картинкой, не экскурсии).\n";
    exit(1);
}

echo "Отобрано " . count($candidates) . " слайд(а):\n";
foreach ($candidates as $i => $c) {
    echo "  " . ($i + 1) . ". {$c['name']}  →  {$c['link_url']}\n";
}
echo "\n";

if ($dryRun) {
    echo "[dry-run] Без --dry-run создало бы " . count($candidates) . " элемент(а) в ИБ {$targetIblockId}\n";
    exit(0);
}

// ── 4. Создаём элементы ────────────────────────────────────────────────────
$el = new CIBlockElement();
$sort = 100;
$created = 0;

foreach ($candidates as $c) {
    // Строим путь к файлу на диске
    $absPath = $_SERVER['DOCUMENT_ROOT'] . $c['pic_path'];
    if (!file_exists($absPath)) {
        // Попробуем без DOCUMENT_ROOT (если путь уже абсолютный)
        $absPath = $c['pic_path'];
    }

    $previewPicture = CFile::MakeFileArray($absPath);
    if (empty($previewPicture)) {
        echo "  WARN: не удалось загрузить картинку {$absPath}, слайд без изображения\n";
        $previewPicture = false;
    } else {
        $previewPicture['name'] = basename($absPath);
    }

    $fields = [
        'IBLOCK_ID'       => $targetIblockId,
        'NAME'            => $c['name'],
        'ACTIVE'          => 'Y',
        'SORT'            => $sort,
        'PREVIEW_PICTURE' => $previewPicture,
        'PROPERTY_VALUES' => [
            'LINK_URL'           => $c['link_url'],
            'SUBTEXT_HTML'       => $c['subtext_html'],
            'AGE_MARK'           => $c['age_mark'],
            'RADARIO_AFISHA_KEY' => $c['radario_afisha_key'],
            'INTICKETS_URL'      => '',
        ],
    ];

    $newId = $el->Add($fields);
    if ($newId) {
        echo "  Создан ID={$newId} «{$c['name']}» SORT={$sort}\n";
        $created++;
    } else {
        echo "  ОШИБКА: {$el->LAST_ERROR}\n";
    }
    $sort += 100;
}

echo "\nГотово. Создано {$created} слайд(а) в ИБ ID={$targetIblockId}.\n";
echo "Главная: http://localhost:18080/\n";
