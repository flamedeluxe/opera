<?php

declare(strict_types=1);

/**
 * AJAX: состав спектакля по дате.
 * GET /local/ajax/event-participants.php?code=ELEMENT_CODE&date=YYYY-MM-DD%20HH:MM:SS
 * Ответ: {"status":"success","html":"..."} или {"status":"error","message":"..."}
 */

define('NO_KEEP_STATISTIC', true);
define('BX_BUFFER_USED', true);
define('NOT_CHECK_PERMISSIONS', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

if (!Bitrix\Main\Loader::includeModule('iblock')) {
    echo json_encode(['status' => 'error', 'message' => 'iblock module unavailable']);
    die();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events.php';

$code = trim((string) ($_GET['code'] ?? ''));
$date = trim((string) ($_GET['date'] ?? ''));

if ($code === '' || $date === '') {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    die();
}

if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $date)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid date format']);
    die();
}

$iblockId = uuopera_afisha_events_iblock_id();
if ($iblockId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Iblock not configured']);
    die();
}

$res = CIBlockElement::GetProperty($iblockId, 0, ['sort' => 'asc'], [
    'CODE' => 'PARTICIPANTS_JSON',
    'ELEMENT_CODE' => $code,
]);

// GetProperty by ELEMENT_CODE is not supported — fetch element ID first
$elRes = CIBlockElement::GetList(
    [],
    ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
    false,
    ['nTopCount' => 1],
    ['ID']
);
$elRow = $elRes->Fetch();
if (!$elRow) {
    echo json_encode(['status' => 'error', 'message' => 'Event not found']);
    die();
}

$elementId = (int) $elRow['ID'];
$propRes = CIBlockElement::GetProperty($iblockId, $elementId, ['sort' => 'asc'], ['CODE' => 'PARTICIPANTS_JSON']);
$propRow = $propRes->Fetch();
$jsonRaw = '';
if ($propRow) {
    $v = $propRow['VALUE'];
    $jsonRaw = is_array($v) ? (string) ($v['TEXT'] ?? ($v[0] ?? '')) : (string) $v;
}

if ($jsonRaw === '') {
    echo json_encode(['status' => 'error', 'message' => 'No participants data']);
    die();
}

$map = json_decode($jsonRaw, true);
if (!is_array($map)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data format']);
    die();
}

$html = $map[$date] ?? null;
if ($html === null) {
    // попробуем без секунд ("2026-05-28 18:30" → "2026-05-28 18:30:00")
    $dateWithSec = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $date) ? $date . ':00' : null;
    if ($dateWithSec !== null) {
        $html = $map[$dateWithSec] ?? null;
    }
}

if ($html === null) {
    echo json_encode(['status' => 'error', 'message' => 'Date not found']);
    die();
}

echo json_encode(['status' => 'success', 'html' => $html], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
