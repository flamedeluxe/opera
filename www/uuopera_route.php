<?php

declare(strict_types=1);

/**
 * Единая точка для urlrewrite.php (пути без физического index.php).
 * ЧПУ сохраняются; обработка — та же, что в uuopera_dispatch_from_script().
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_dispatch.php';

$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = parse_url($uri, PHP_URL_PATH);
$path = is_string($path) ? $path : '/';
$path = str_replace('\\', '/', $path);
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

uuopera_dispatch_for_path($path);
