<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_page.php';

uuopera_page([
    'title' => 'События - Бурятский театр оперы и балета',
    'include' => '/local/templates/uuopera/includes/page_afisha.php',
    'extra_css' => ['tpl/css/page-beige.css'],
    'footer_js' => [],
]);
