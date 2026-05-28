<?php

declare(strict_types=1);

define('NO_KEEP_STATISTIC', true);
define('BX_BUFFER_USED', true);
define('NOT_CHECK_PERMISSIONS', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..') ?: '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!function_exists('uuopera_contacts_feedback_handle')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_contacts_feedback.php';
}

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

echo json_encode(uuopera_contacts_feedback_handle(), JSON_UNESCAPED_UNICODE);
