<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

foreach ($arResult['ITEMS'] as &$item) {
    $code = isset($item['CODE']) ? trim((string) $item['CODE']) : '';
    $eid = (int) ($item['ID'] ?? 0);
    if ($code !== '') {
        $from = $item['ACTIVE_FROM'] ?? $item['DATE_ACTIVE_FROM'] ?? '';
        $ts = 0;
        if ($from !== '') {
            $ts = (int) MakeTimeStamp($from);
            if ($ts <= 0) {
                $ts = (int) strtotime($from);
            }
        }
        if ($ts > 0) {
            $item['UUOPERA_DETAIL_URL'] = '/' . date('Y/m/d', $ts) . '/' . $code . '/';
        }
    }
    if (empty($item['UUOPERA_DETAIL_URL']) && $eid > 0) {
        $item['UUOPERA_DETAIL_URL'] = '/category/news/detail/?id=' . $eid;
    }
}
unset($item);

$nav = $arResult['NAV_RESULT'] ?? null;
$currentPage = 1;
$pageCount = 1;
if (is_object($nav)) {
    $currentPage = max(1, (int) ($nav->NavPageNomer ?? 1));
    $pageCount = max(1, (int) ($nav->NavPageCount ?? 1));
}

$arResult['UUOPERA_HAS_MORE'] = $currentPage < $pageCount;
$arResult['UUOPERA_NEXT_PAGE_URL'] = '';
if ($arResult['UUOPERA_HAS_MORE']) {
    $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/category/news/'), '?') ?: '/category/news/';
    $params = $_GET;
    $next = $currentPage + 1;
    $params['page'] = (string) $next;
    unset($params['PAGEN_1']);
    $arResult['UUOPERA_NEXT_PAGE_URL'] = $path . '?' . http_build_query($params);
}
