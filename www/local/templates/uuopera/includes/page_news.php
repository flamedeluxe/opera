<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */
$APPLICATION->IncludeComponent(
    'bitrix:news.list',
    'uuopera',
    [
        'IBLOCK_TYPE' => 'news',
        'IBLOCK_ID' => uuopera_news_iblock_id(),
        'NEWS_COUNT' => 12,
        'SORT_BY1' => 'ACTIVE_FROM',
        'SORT_ORDER1' => 'DESC',
        'SORT_BY2' => 'SORT',
        'SORT_ORDER2' => 'ASC',
        'FIELD_CODE' => ['NAME', 'DATE_ACTIVE_FROM', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
        'PROPERTY_CODE' => [],
        'CHECK_DATES' => 'Y',
        'DISPLAY_BOTTOM_PAGER' => 'Y',
        'PAGER_SHOW_ALWAYS' => 'N',
        'PAGER_TEMPLATE' => '.default',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '3600',
        'CACHE_GROUPS' => 'Y',
        'SET_TITLE' => 'N',
        'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
        'ACTIVE_DATE_FORMAT' => 'd.m.Y',
    ],
    false
);
