<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_prune_empty_iblock_sections.php [--dry-run]
 * Удаляет пустые разделы инфоблоков type=uuopera, не используемые на сайте.
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

$dry = in_array('--dry-run', array_slice($argv, 1), true);

$keepSectionCodes = [
    'corporate_news_s1' => ['news', 'oficialnaya-informaciya'],
    'uuopera_afisha_events' => [
        'opera', 'ballet', 'concert', 'excursions', 'festivals', 'online',
        'performances', 'no-category', 'abonement', 'musical', 'fairytale', 'operetta',
    ],
    'uuopera_megamenu' => ['*'],
    'uuopera_persone' => ['*'],
];

function uuopera_prune_section_element_count(int $iblockId, int $sectionId): int
{
    $res = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            'SECTION_ID' => $sectionId,
            'INCLUDE_SUBSECTIONS' => 'Y',
            'CHECK_PERMISSIONS' => 'N',
        ],
        []
    );

    return (int) $res;
}

function uuopera_prune_code_kept(string $iblockCode, string $sectionCode, array $keepSectionCodes): bool
{
    $rules = $keepSectionCodes[$iblockCode] ?? [];
    if ($rules === []) {
        return false;
    }
    if (in_array('*', $rules, true)) {
        return true;
    }
    $sectionCode = strtolower($sectionCode);

    return in_array($sectionCode, $rules, true);
}

$deleted = 0;
$kept = 0;

$iblockCodes = [];
$res = CIBlock::GetList(['ID' => 'ASC'], ['TYPE' => 'uuopera', 'CHECK_PERMISSIONS' => 'N']);
while ($ib = $res->Fetch()) {
    $iblockId = (int) ($ib['ID'] ?? 0);
    if ($iblockId <= 0) {
        continue;
    }
    $iblockCodes[$iblockId] = (string) ($ib['CODE'] ?? '');
}

foreach ($iblockCodes as $iblockId => $iblockCode) {
    $secRes = CIBlockSection::GetList(
        ['LEFT_MARGIN' => 'DESC'],
        ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'NAME', 'CODE', 'DEPTH_LEVEL']
    );
    while ($sec = $secRes->Fetch()) {
        $secId = (int) ($sec['ID'] ?? 0);
        $secCode = trim((string) ($sec['CODE'] ?? ''));
        if ($secId <= 0) {
            continue;
        }

        $childRes = CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'SECTION_ID' => $secId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID']
        );
        if ($childRes && $childRes->Fetch()) {
            echo "KEEP (has child sections): {$iblockCode} / {$secCode} ID={$secId}\n";
            $kept++;
            continue;
        }

        $cnt = uuopera_prune_section_element_count($iblockId, $secId);
        if ($cnt > 0) {
            $kept++;
            continue;
        }

        if (uuopera_prune_code_kept($iblockCode, $secCode, $keepSectionCodes)) {
            echo "KEEP (used in site): {$iblockCode} / {$secCode} ID={$secId}\n";
            $kept++;
            continue;
        }

        $label = "{$iblockCode} / " . ($secCode !== '' ? $secCode : '(no code)') . " / {$sec['NAME']} ID={$secId}";
        if ($dry) {
            echo "[dry] DELETE {$label}\n";
            $deleted++;
            continue;
        }
        if (CIBlockSection::Delete($secId)) {
            echo "DELETE {$label}\n";
            $deleted++;
        } else {
            global $APPLICATION;
            $msg = ($APPLICATION && $APPLICATION->GetException()) ? $APPLICATION->GetException()->GetString() : '?';
            fwrite(STDERR, "FAIL {$label}: {$msg}\n");
        }
    }
}

echo ($dry ? '(dry-run) ' : '') . "Deleted: {$deleted}, kept: {$kept}\n";
