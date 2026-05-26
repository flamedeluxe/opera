<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_page.php';

uuopera_page([
    'title' => 'Бурятский театр оперы и балета',
    'include' => '/local/templates/uuopera/includes/main_home.php',
    'extra_css' => [],
    'footer_js' => ['tpl/js/page-index.js', 'tpl/js/video-cover.js'],
]);
