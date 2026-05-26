<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */
$code = (string) ($GLOBALS['UUOPERA_NEWS_ELEMENT_CODE'] ?? '');
$id = (int) ($GLOBALS['UUOPERA_NEWS_ELEMENT_ID'] ?? 0);

$detailParams = [
        'IBLOCK_TYPE' => 'uuopera',
        'IBLOCK_ID' => uuopera_news_iblock_id(),
        'FIELD_CODE' => ['NAME', 'DETAIL_TEXT', 'PREVIEW_TEXT', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'DATE_ACTIVE_FROM'],
        'PROPERTY_CODE' => [],
        'SET_TITLE' => 'N',
        'SET_CANONICAL_URL' => 'N',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '3600',
        'CACHE_GROUPS' => 'Y',
        'CHECK_PERMISSIONS' => 'Y',
];
if ($id > 0) {
    $detailParams['ELEMENT_ID'] = $id;
} else {
    $detailParams['ELEMENT_CODE'] = $code;
}

$APPLICATION->IncludeComponent(
    'bitrix:news.detail',
    'uuopera',
    $detailParams,
    false
);
