<?php

/**
 * Создаёт инфоблок «События афиши» и свойства.
 *
 * CLI: php local/tools/uuopera_afisha_events_install.php
 */

declare(strict_types=1);

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
    echo "Модуль iblock не установлен.\n";
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events_bootstrap.php';

try {
    $r = uuopera_afisha_events_bootstrap_iblock();
} catch (Throwable $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

echo "Готово. Инфоблок «События афиши» ID={$r['iblock_id']}.\n";
echo "Импорт: php local/tools/uuopera_afisha_bulk_import_uuopera.php\n";
