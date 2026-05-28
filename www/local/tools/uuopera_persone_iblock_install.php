<?php

declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_persone_iblock_install.php
 * Создаёт инфоблок «Персоналии» и сохраняет ID в настройки.
 */

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module missing\n");
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_cms_iblocks_bootstrap.php';

$bid = uuopera_cms_ensure_iblock('uuopera_persone', 'Персоналии', 480, [
    [
        'NAME' => 'Должность / роль',
        'ACTIVE' => 'Y',
        'SORT' => 110,
        'CODE' => 'ROLE',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE' => 'N',
        'ROW_COUNT' => 1,
        'COL_COUNT' => 200,
    ],
    [
        'NAME' => 'URL фото (внешний, если нет PREVIEW_PICTURE)',
        'ACTIVE' => 'Y',
        'SORT' => 120,
        'CODE' => 'PHOTO_URL',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE' => 'N',
        'ROW_COUNT' => 1,
        'COL_COUNT' => 500,
    ],
    [
        'NAME' => 'Подгруппы (для группировки в списке)',
        'ACTIVE' => 'Y',
        'SORT' => 115,
        'CODE' => 'GROUPS',
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE' => 'Y',
        'ROW_COUNT' => 1,
        'COL_COUNT' => 200,
    ],
], true);

\Bitrix\Main\Config\Option::set('uuopera', 'persone_iblock_id', (string) $bid);
echo "persone_iblock_id = $bid\n";
echo "Запустите: php local/tools/uuopera_persone_sections_install.php\n";
echo "OK\n";
