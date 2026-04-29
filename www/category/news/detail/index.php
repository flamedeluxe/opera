<?php

declare(strict_types=1);

$GLOBALS['UUOPERA_NEWS_ELEMENT_ID'] = (int) ($_GET['id'] ?? 0);
unset($GLOBALS['UUOPERA_NEWS_ELEMENT_CODE']);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_page.php';

uuopera_page([
    'title_callback' => 'uuopera_news_apply_detail_title',
    'include' => '/local/templates/uuopera/includes/page_news_detail_iblock.php',
    'extra_css' => [],
    'footer_js' => [],
]);
