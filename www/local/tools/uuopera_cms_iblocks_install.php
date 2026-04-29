<?php

declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_cms_iblocks_install.php
 * Создаёт инфоблоки CMS и записывает ID в настройки uuopera/*_iblock_id.
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

$ids = uuopera_cms_bootstrap_iblocks();
echo "uuopera_static_pages: {$ids['static_pages']}\n";
echo "uuopera_home_slides: {$ids['home_slides']}\n";
echo "uuopera_projects: {$ids['projects']}\n";
echo "uuopera_about_blocks: {$ids['about']}\n";
echo "uuopera_service_faq: {$ids['service_faq']}\n";
echo "OK\n";
