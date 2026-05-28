<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_megamenu_sync_personalii.php
 * Синхронизирует пункты «Персоны» в мегаменю с разделами инфоблока «Персоналии».
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..') ?: '';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module missing\n");
    exit(1);
}

$iblockId = uuopera_megamenu_iblock_id();
if ($iblockId <= 0) {
    fwrite(STDERR, "megamenu_iblock_id=0\n");
    exit(1);
}

$secRes = CIBlockSection::GetList(
    [],
    ['IBLOCK_ID' => $iblockId, '=XML_ID' => 'uu_m_persons', 'CHECK_PERMISSIONS' => 'N'],
    false,
    ['nTopCount' => 1],
    ['ID']
);
$secRow = $secRes ? $secRes->Fetch() : false;
$sectionId = is_array($secRow) ? (int) ($secRow['ID'] ?? 0) : 0;
if ($sectionId <= 0) {
    fwrite(STDERR, "Раздел uu_m_persons не найден. Запустите uuopera_megamenu_iblock_install.php\n");
    exit(1);
}

$expected = [];
foreach (uuopera_persone_megamenu_link_items() as [$title, $url]) {
    $expected[$url] = $title;
}

$byUrl = [];
$elRes = CIBlockElement::GetList(
    ['SORT' => 'ASC', 'ID' => 'ASC'],
    ['IBLOCK_ID' => $iblockId, 'SECTION_ID' => $sectionId, 'CHECK_PERMISSIONS' => 'N'],
    false,
    false,
    ['ID', 'NAME', 'SORT']
);
while ($row = $elRes->Fetch()) {
    $elId = (int) ($row['ID'] ?? 0);
    if ($elId <= 0) {
        continue;
    }
    $link = '';
    $propRes = CIBlockElement::GetProperty($iblockId, $elId, [], ['CODE' => 'LINK']);
    while ($p = $propRes->Fetch()) {
        $link = trim((string) ($p['VALUE'] ?? ''));
        if ($link !== '') {
            break;
        }
    }
    if ($link !== '') {
        $byUrl[$link] = ['id' => $elId, 'name' => (string) ($row['NAME'] ?? ''), 'sort' => (int) ($row['SORT'] ?? 500)];
    }
}

$el = new CIBlockElement();
$sort = 100;
$added = 0;
$updated = 0;
$removed = 0;

foreach ($expected as $url => $title) {
    if (isset($byUrl[$url])) {
        $cur = $byUrl[$url];
        if ($cur['name'] !== $title || $cur['sort'] !== $sort) {
            $el->Update($cur['id'], ['NAME' => $title, 'SORT' => $sort]);
            $updated++;
        }
        unset($byUrl[$url]);
    } else {
        $newId = (int) $el->Add([
            'IBLOCK_ID' => $iblockId,
            'IBLOCK_SECTION_ID' => $sectionId,
            'NAME' => $title,
            'ACTIVE' => 'Y',
            'SORT' => $sort,
            'PROPERTY_VALUES' => ['LINK' => $url],
        ]);
        if ($newId > 0) {
            $added++;
        } else {
            global $APPLICATION;
            $msg = ($APPLICATION && $APPLICATION->GetException()) ? $APPLICATION->GetException()->GetString() : '?';
            fwrite(STDERR, "Add {$title}: {$msg}\n");
        }
    }
    $sort += 100;
}

foreach ($byUrl as $url => $cur) {
    if (!str_starts_with($url, '/personalii/')) {
        continue;
    }
    CIBlockElement::Delete($cur['id']);
    $removed++;
    echo "Removed obsolete: {$url}\n";
}

echo "OK: added={$added}, updated={$updated}, removed={$removed}\n";
