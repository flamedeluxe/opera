<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_page.php';

uuopera_page([
    'title' => 'Новости - Бурятский театр оперы и балета',
    'include' => '/local/templates/uuopera/includes/page_news.php',
    'extra_css' => [],
    'footer_js' => [],
]);
