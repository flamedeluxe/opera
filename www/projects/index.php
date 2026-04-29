<?php

declare(strict_types=1);

unset($GLOBALS['UUOPERA_PROJECT_CODE']);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_page.php';

uuopera_page([
    'title' => 'Проекты - Бурятский театр оперы и балета',
    'include' => '/local/templates/uuopera/includes/page_projects.php',
    'extra_css' => ['tpl/css/page-blue.css'],
    'footer_js' => [],
]);
