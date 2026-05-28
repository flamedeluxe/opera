<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_persone_sections_install.php
 * Разделы инфоблока «Персоналии» (вместо свойства CATEGORY).
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

$iblockId = uuopera_persone_iblock_id();
if ($iblockId <= 0) {
    fwrite(STDERR, "persone_iblock_id=0\n");
    exit(1);
}

(new CIBlock())->Update($iblockId, [
    'SECTIONS' => 'Y',
    'INDEX_SECTION' => 'Y',
]);

uuopera_persone_ensure_all_sections();
foreach (uuopera_persone_sections_definitions() as $code => $def) {
    $id = uuopera_persone_section_id_by_code((string) $code);
    echo "Section {$code} => ID {$id}\n";
}

echo "OK\n";
