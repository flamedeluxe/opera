<?php

declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_about_seed.php
 * Заполняет инфоблок «О театре: блоки» (iblock_id=8) данными из about_data.json.
 * Идемпотентен: обновляет по XML_ID.
 */

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "Модуль iblock не подключён\n");
    exit(1);
}

require $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_cms_iblocks_bootstrap.php';

// Ensure properties exist (adds SIDE_IMAGE_URL, SIDE_CAPTION, DIAGRAM_IMAGE_URL if missing)
uuopera_cms_bootstrap_iblocks();

$iblockId = uuopera_cms_about_iblock_id();
if ($iblockId <= 0) {
    fwrite(STDERR, "Не удалось получить ID инфоблока 'О театре: блоки'\n");
    exit(1);
}
echo "Iblock ID: $iblockId\n";

$jsonPath = __DIR__ . '/about_data.json';
if (!file_exists($jsonPath)) {
    fwrite(STDERR, "Файл $jsonPath не найден\n");
    exit(1);
}
$data = json_decode(file_get_contents($jsonPath), true);

$ok = 0;
$skip = 0;
$err = 0;

function about_upsert_element(int $iblockId, string $xmlId, string $name, int $sort, array $fields, array $props): void
{
    global $ok, $skip, $err;

    $existing = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID']
    )->Fetch();

    $el = new CIBlockElement();
    $baseFields = array_merge([
        'IBLOCK_ID'    => $iblockId,
        'NAME'         => $name,
        'XML_ID'       => $xmlId,
        'SORT'         => $sort,
        'ACTIVE'       => 'Y',
        'PREVIEW_TEXT' => '',
    ], $fields);

    if ($existing) {
        $id = (int) $existing['ID'];
        $res = $el->Update($id, $baseFields);
        if (!$res) {
            global $APPLICATION;
            echo "ERR update $xmlId: " . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown') . "\n";
            $err++;
            return;
        }
    } else {
        $id = (int) $el->Add($baseFields);
        if ($id <= 0) {
            global $APPLICATION;
            echo "ERR add $xmlId: " . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown') . "\n";
            $err++;
            return;
        }
    }

    CIBlockElement::SetPropertyValuesEx($id, $iblockId, $props);
    $existing ? $skip++ : $ok++;
    $label = $existing ? 'UPD' : 'ADD';
    echo "$label $xmlId\n";
}

// ===== TIMELINE =====
foreach ($data['timeline'] as $i => $item) {
    $year  = (string) ($item['year'] ?? '');
    $title = (string) ($item['title'] ?? '');
    $body  = (string) ($item['body_html'] ?? '');
    $sideUrl = (string) ($item['side_img'] ?? '');
    $sideCaption = (string) ($item['side_caption'] ?? '');
    $xmlId = 'about_timeline_' . $year . '_' . ($i + 1);

    $props = [
        'BLOCK_KIND'     => 'timeline',
        'YEAR_LABEL'     => $year,
        'BLOCK_TITLE'    => $title,
        'BODY_HTML'      => $body,
        'SIDE_IMAGE_URL' => $sideUrl,
        'SIDE_CAPTION'   => $sideCaption,
    ];

    // Try to download side image
    if ($sideUrl !== '') {
        $fileArr = CFile::MakeFileArray($sideUrl);
        if (!empty($fileArr) && !empty($fileArr['tmp_name'])) {
            $props['SIDE_IMAGE'] = $fileArr;
        }
    }

    about_upsert_element($iblockId, $xmlId, $year . ' — ' . $title, ($i + 1) * 10, [], $props);
}

// ===== MISSION SLIDES =====
$themeOrder = ['white' => 1, 'brown' => 2, 'blue' => 3];
foreach ($data['mission_slides'] as $ms) {
    $theme  = (string) ($ms['theme'] ?? 'white');
    $lead   = (string) ($ms['lead'] ?? '');
    $body   = (string) ($ms['body_html'] ?? '');
    $diagUrl = (string) ($ms['diagram'] ?? '');
    $sortBase = 100 + ($themeOrder[$theme] ?? 9) * 10;
    $xmlId = 'about_mission_' . $theme;

    $props = [
        'BLOCK_KIND'        => 'mission',
        'THEME'             => $theme,
        'BLOCK_TITLE'       => $lead,
        'BODY_HTML'         => $body,
        'DIAGRAM_IMAGE_URL' => $diagUrl,
    ];

    if ($diagUrl !== '') {
        $fileArr = CFile::MakeFileArray($diagUrl);
        if (!empty($fileArr) && !empty($fileArr['tmp_name'])) {
            $props['DIAGRAM_IMAGE'] = $fileArr;
        }
    }

    about_upsert_element($iblockId, $xmlId, 'Миссия — ' . $theme, $sortBase, [], $props);
}

echo "\nOK=$ok SKIP=$skip ERR=$err\n";
