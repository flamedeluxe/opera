<?php

declare(strict_types=1);

require_once __DIR__ . '/uuopera_page.php';

/**
 * Вызов из любого сгенерированного …/index.php: маршрут по каталогу скрипта.
 */
function uuopera_dispatch_from_script(): void
{
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $dir = str_replace('\\', '/', dirname($script));
    $path = rtrim($dir, '/');
    if ($path === '' || $path === '.') {
        $path = '/';
    }
    // Если запрос попал в корневой index.php (try_files), SCRIPT_NAME = /index.php — берём путь из URI.
    if ($path === '/') {
        $raw = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $req = $raw !== false && $raw !== null ? rtrim(str_replace('\\', '/', (string) $raw), '/') : '';
        if ($req !== '' && preg_match('#^/afisha/#', $req)) {
            $path = $req;
        }
    }
    uuopera_dispatch_for_path($path);
}

function uuopera_dispatch_for_path(string $path): void
{
    $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    $path = rtrim(str_replace('\\', '/', $path), '/');
    if ($path === '') {
        $path = '/';
    }

    $afishaCats = [
        'opera', 'ballet', 'concert', 'excursions', 'festivals',
        'online', 'performances', 'no-category', 'abonement', 'musical',
    ];

    foreach ($afishaCats as $cat) {
        if ($path === '/afisha/' . $cat) {
            $GLOBALS['UUOPERA_AFISHA_LIST_CATEGORY'] = $cat;
            uuopera_page([
                'title' => 'События - Бурятский театр оперы и балета',
                'include' => '/local/templates/uuopera/includes/page_afisha.php',
                'extra_css' => ['tpl/css/page-beige.css'],
                'footer_js' => [],
            ]);
            return;
        }
    }

    if (preg_match('#^/afisha/([^/]+)/([^/]+)$#', $path, $afishaM)) {
        $GLOBALS['UUOPERA_AFISHA_CATEGORY'] = (string) $afishaM[1];
        $GLOBALS['UUOPERA_AFISHA_CODE'] = (string) $afishaM[2];
        uuopera_page([
            'title_callback' => 'uuopera_afisha_event_apply_title',
            'include' => '/local/templates/uuopera/includes/page_afisha_item_dynamic.php',
            'extra_css' => [],
            'footer_js' => ['tpl/js/page-single-event.js'],
        ]);
        return;
    }

    if (preg_match('#^/20[0-9]{2}/[0-9]{2}/[0-9]{2}/([^/]+)$#', $path, $newsM)) {
        $GLOBALS['UUOPERA_NEWS_ELEMENT_CODE'] = (string) $newsM[1];
        uuopera_page([
            'title_callback' => 'uuopera_news_apply_detail_title',
            'include' => '/local/templates/uuopera/includes/page_news_detail_iblock.php',
            'extra_css' => [],
            'footer_js' => [],
        ]);
        return;
    }

    if ($path === '/balet_na_baikale') {
        unset($GLOBALS['UUOPERA_PROJECT_CODE']);
        uuopera_page([
            'title' => 'Проекты - Бурятский театр оперы и балета',
            'include' => '/local/templates/uuopera/includes/page_projects.php',
            'extra_css' => ['tpl/css/page-blue.css'],
            'footer_js' => [],
        ]);
        return;
    }

    if (preg_match('#^/projects/([^/]+)$#', $path, $pm)) {
        $GLOBALS['UUOPERA_PROJECT_CODE'] = (string) $pm[1];
        uuopera_page([
            'title_callback' => 'uuopera_cms_project_apply_title',
            'include' => '/local/templates/uuopera/includes/page_projects.php',
            'extra_css' => ['tpl/css/page-blue.css'],
            'footer_js' => [],
        ]);
        return;
    }

    if (preg_match('#^/personalii/#', $path)) {
        uuopera_stub_page('Персоны', 'Список персон будет вынесен в инфоблоки Битрикс.');
        return;
    }

    if (preg_match('#^/for-visitors/#', $path)) {
        uuopera_stub_page('Посетителям', 'Материалы для посетителей переносятся из WordPress.');
        return;
    }

    if (preg_match('#^/persone/#', $path)) {
        uuopera_stub_page('Персона', 'Карточка персоны будет в Битрикс.');
        return;
    }

    if ($doc !== '' && !defined('START_EXEC_PROLOG_BEFORE_2') && is_file($doc . '/bitrix/modules/main/include/prolog_before.php')) {
        require_once $doc . '/bitrix/modules/main/include/prolog_before.php';
    }

    $static = uuopera_cms_static_page_find($path);
    if ($static !== null) {
        $GLOBALS['UUOPERA_CMS_STATIC_TITLE'] = $static['title'];
        $GLOBALS['UUOPERA_CMS_STATIC_HTML'] = $static['html'];
        $GLOBALS['UUOPERA_CMS_STATIC_HEADER_SCHEMA'] = $static['header_schema'];
        uuopera_page([
            'title_callback' => 'uuopera_cms_static_page_apply_title',
            'include' => '/local/templates/uuopera/includes/page_static_cms.php',
            'extra_css' => ['tpl/css/page-beige.css'],
            'footer_js' => [],
        ]);
        return;
    }

    $stubMap = [
        '/documents' => 'Документы',
        '/brandbook' => 'Брендбук',
        '/category/oficialnaya-informaciya' => 'Официальная информация',
        '/soglasie-na-obrabotku-personalnykh-d' => 'Согласие на обработку персональных данных',
    ];
    if (isset($stubMap[$path])) {
        uuopera_stub_page($stubMap[$path], '');
        return;
    }

    uuopera_stub_page('Страница', 'Маршрут не сопоставлён. Добавьте правило в uuopera_dispatch.php или создайте отдельный index.php.');
}

function uuopera_stub_page(string $title, string $lead = ''): void
{
    $GLOBALS['UUOPERA_STUB_TITLE'] = $title;
    unset($GLOBALS['UUOPERA_STUB_LEAD']);
    if ($lead !== '') {
        $GLOBALS['UUOPERA_STUB_LEAD'] = $lead;
    }
    uuopera_page([
        'title' => $title . ' - Бурятский театр оперы и балета',
        'include' => '/local/templates/uuopera/includes/page_stub.php',
        'extra_css' => ['tpl/css/page-beige.css'],
        'footer_js' => [],
    ]);
}
