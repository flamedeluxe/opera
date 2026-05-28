<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_persone_remove_category_property.php
 * Удаляет устаревшее свойство CATEGORY из инфоблока «Персоналии».
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

$row = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'CATEGORY'])->Fetch();
if (!$row) {
    echo "Свойство CATEGORY отсутствует.\n";
    exit(0);
}

$propId = (int) ($row['ID'] ?? 0);
if ($propId > 0 && CIBlockProperty::Delete($propId)) {
    echo "Удалено свойство CATEGORY (ID={$propId}).\n";
    exit(0);
}

fwrite(STDERR, "Не удалось удалить свойство CATEGORY.\n");
exit(1);
