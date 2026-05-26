<?php

declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_seed_admin_iblock_sections.php
 * Повторно создаёт пустые разделы в уже существующих инфоблоках (идемпотентно).
 */

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
    fwrite(STDERR, "Модуль iblock не подключён\n");
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_cms_iblocks_bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_cms_data.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events_bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events.php';

foreach (['uuopera_contacts_settings', 'uuopera_static_pages', 'uuopera_home_slides', 'uuopera_projects', 'uuopera_about_blocks', 'uuopera_service_faq'] as $code) {
    $row = CIBlock::GetList([], ['TYPE' => 'uuopera', 'CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($row) {
        (new CIBlock())->Update((int) $row['ID'], [
            'SECTIONS' => 'Y',
            'INDEX_SECTION' => 'Y',
        ]);
    }
}

$ids = [
    'contacts' => uuopera_cms_contacts_iblock_id(),
    'static_pages' => uuopera_cms_static_pages_iblock_id(),
    'home_slides' => uuopera_cms_home_slides_iblock_id(),
    'projects' => uuopera_cms_projects_iblock_id(),
    'about' => uuopera_cms_about_iblock_id(),
    'service_faq' => uuopera_cms_service_faq_iblock_id(),
];
uuopera_cms_seed_iblock_admin_sections($ids);

$afId = uuopera_afisha_events_iblock_id();
if ($afId > 0) {
    uuopera_afisha_events_seed_category_sections($afId);
    echo "uuopera_afisha_events: разделы-категории, IBLOCK_ID={$afId}\n";
} else {
    echo "uuopera_afisha_events: инфоблок не настроен (запустите uuopera_afisha_events_install.php)\n";
}

echo "CMS contacts={$ids['contacts']} static_pages={$ids['static_pages']} home_slides={$ids['home_slides']} projects={$ids['projects']} about={$ids['about']} service_faq={$ids['service_faq']}\n";
echo "OK\n";
