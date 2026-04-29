<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_page.php';

uuopera_page([
    'title' => 'О театре - Бурятский театр оперы и балета',
    'include' => '/local/templates/uuopera/includes/page_about.php',
    'extra_css' => [],
    'footer_js' => [],
]);
