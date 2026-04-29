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
