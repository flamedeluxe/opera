<?php

/**
 * Создаёт тип инфоблока, инфоблок «Экскурсии (афиша)», раздел и свойства (TV).
 * Контент не добавляет — заполнение: php local/tools/uuopera_excursions_import_uuopera.php
 *
 * CLI: php local/tools/uuopera_excursions_iblock_install.php
 * Браузер (админ): /local/tools/uuopera_excursions_iblock_install.php
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

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_excursions_iblock_bootstrap.php';

try {
    $struct = uuopera_excursions_bootstrap_structure();
} catch (Throwable $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}

$bid = $struct['iblock_id'];
echo "Готово. Инфоблок ID={$bid}, раздел «Экскурсии» ID={$struct['section_id']}.\n";
echo "Импорт контента с uuopera.ru: php local/tools/uuopera_excursions_import_uuopera.php\n";
echo "Админка: Контент → Информационные блоки → uuopera.ru → Экскурсии (афиша).\n";
echo "Очистите кеш сайта.\n";
