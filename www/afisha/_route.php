<?php

declare(strict_types=1);

/**
 * Fallback, если нет физической папки …/afisha/{категория}/{код}/ (nginx try_files).
 * Карточка строится по символьному коду в ИБ «События афиши»; сегмент категории в URL может не совпадать с реальной.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_dispatch.php';

$raw = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$path = $raw !== false && $raw !== null
    ? rtrim(str_replace('\\', '/', (string) $raw), '/')
    : '/';

uuopera_dispatch_for_path($path);
