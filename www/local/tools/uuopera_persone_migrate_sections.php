<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_persone_migrate_sections.php [--dry-run]
 * Переносит значения свойства CATEGORY в привязку к разделам инфоблока.
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
$iblockId = uuopera_persone_iblock_id();
if ($iblockId <= 0) {
    fwrite(STDERR, "persone_iblock_id=0\n");
    exit(1);
}

$updated = 0;
$skipped = 0;

$res = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
    false,
    false,
    ['ID', 'NAME']
);

while ($row = $res->Fetch()) {
    $elId = (int) ($row['ID'] ?? 0);
    if ($elId <= 0) {
        continue;
    }
    $props = [];
    CIBlockElement::GetPropertyValuesArray($props, $iblockId, ['ID' => $elId], ['CODE' => ['CATEGORY']]);
    $codes = uuopera_persone_category_codes_from_legacy_property($props, $elId);
    if ($codes === []) {
        $codes = uuopera_persone_element_section_codes($elId);
    }
    if ($codes === []) {
        $skipped++;
        continue;
    }
    if ($dry) {
        echo "[dry] #{$elId} " . ($row['NAME'] ?? '') . ' → ' . implode(', ', $codes) . "\n";
        $updated++;
        continue;
    }
    if (uuopera_persone_assign_element_sections($elId, $codes)) {
        CIBlockElement::SetPropertyValuesEx($elId, $iblockId, ['CATEGORY' => false]);
        $updated++;
    }
}

echo ($dry ? '(dry-run) ' : '') . "Updated: {$updated}, skipped: {$skipped}\n";
