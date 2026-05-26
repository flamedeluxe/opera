<?php

declare(strict_types=1);

/**
 * CLI: cd /var/www/bitrix && php local/tools/uuopera_full_setup.php
 * Дочерние скрипты запускаются отдельными процессами PHP (без повторного prolog).
 */

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Только CLI.';
    exit;
}

$www = realpath(__DIR__ . '/../..');
if ($www === false) {
    fwrite(STDERR, "не найден корень сайта\n");
    exit(1);
}

$php = defined('PHP_BINARY') && PHP_BINARY !== '' ? PHP_BINARY : 'php';

$tools = [
    'local/tools/uuopera_cms_iblocks_install.php',
    'local/tools/uuopera_megamenu_iblock_install.php',
    'local/tools/uuopera_afisha_events_install.php',
    'local/tools/uuopera_seed_admin_iblock_sections.php',
];

foreach ($tools as $rel) {
    $script = $www . '/' . $rel;
    if (!is_file($script)) {
        fwrite(STDERR, "пропуск: нет {$rel}\n");
        continue;
    }
    echo "\n=== {$rel} ===\n";
    passthru($php . ' ' . escapeshellarg($script), $code);
    if ($code !== 0) {
        fwrite(STDERR, "выход с кодом {$code}\n");
        exit($code);
    }
}

echo "\nГотово. Импорт карточек афиши (сеть): php local/tools/uuopera_afisha_bulk_import_uuopera.php\n";
exit(0);
