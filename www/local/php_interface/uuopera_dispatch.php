<?php

declare(strict_types=1);

require_once __DIR__ . '/uuopera_page.php';
require_once __DIR__ . '/uuopera_persone_sections.php';

/**
 * Вызов из любого сгенерированного …/index.php: маршрут по каталогу скрипта.
 */
function uuopera_dispatch_from_script(): void
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '') {
        uuopera_dispatch_for_path('/');
        return;
    }

    $name = (string) pathinfo($script, PATHINFO_FILENAME);
    $dirRaw = dirname($script);
    $dirTail = trim(str_replace('\\', '/', $dirRaw), '/');

    if ($name !== '' && strcasecmp($name, 'index') !== 0) {
        $path = ($dirTail === '') ? '/' . $name : '/' . $dirTail . '/' . $name;
    } else {
        $path = ($dirTail === '') ? '/' : '/' . $dirTail;
    }

    $path = rtrim(str_replace('//', '/', $path), '/');
    if ($path === '') {
        $path = '/';
    }
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

    if ($path === '/company/history') {
        header('Location: /missiya-i-cennosti/#history', true, 301);
        exit;
    }
    if ($path === '/company/mission') {
        header('Location: /missiya-i-cennosti/#mission', true, 301);
        exit;
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
            'extra_css' => ['tpl/css/page-beige.css'],
            'footer_js' => [],
        ]);
        return;
    }

    if ($path === '/services') {
        uuopera_page([
            'title' => 'Платные услуги - Бурятский театр оперы и балета',
            'include' => '/local/templates/uuopera/includes/page_services.php',
            'extra_css' => ['tpl/css/page-beige.css'],
            'footer_js' => [],
        ]);
        return;
    }

    $personaliiSections = uuopera_persone_sections_catalog();
    $personaliiCats = [];
    foreach ($personaliiSections as $code => $sec) {
        $personaliiCats[$code] = (string) ($sec['name'] ?? $code);
    }
    if ($personaliiCats === []) {
        foreach (uuopera_persone_sections_definitions() as $code => $def) {
            $personaliiCats[$code] = (string) $def['name'];
        }
    }
    $personaliiGroupOrder = uuopera_persone_group_order_map();

    if (preg_match('#^/personalii/([^/]+)$#', $path, $ppm)) {
        $catSlug = (string) $ppm[1];
        if (isset($personaliiCats[$catSlug])) {
            $GLOBALS['UUOPERA_PERSONALII_CATEGORY'] = $catSlug;
            $GLOBALS['UUOPERA_PERSONALII_TITLE'] = $personaliiCats[$catSlug];
            $GLOBALS['UUOPERA_PERSONALII_CATS'] = $personaliiCats;
            $GLOBALS['UUOPERA_PERSONALII_GROUP_ORDER'] = $personaliiGroupOrder[$catSlug] ?? [];
            uuopera_page([
                'title' => $personaliiCats[$catSlug] . ' - Бурятский театр оперы и балета',
                'include' => '/local/templates/uuopera/includes/page_personalii.php',
                'extra_css' => ['tpl/css/page-beige.css'],
                'footer_js' => [],
            ]);
            return;
        }
    }

    if (preg_match('#^/persone/([^/]+)$#', $path, $ppm)) {
        $GLOBALS['UUOPERA_PERSONE_SLUG'] = (string) $ppm[1];
        uuopera_page([
            'title_callback' => 'uuopera_persone_apply_title',
            'include' => '/local/templates/uuopera/includes/page_persone_detail.php',
            'extra_css' => ['tpl/css/page-beige.css'],
            'footer_js' => [],
        ]);
        return;
    }

    if ($path === '/for-visitors' || str_starts_with($path, '/for-visitors/')) {
        $GLOBALS['UUOPERA_FOR_VISITORS_PATH'] = $path;
        uuopera_page([
            'title' => 'Посетителям театра - Бурятский театр оперы и балета',
            'include' => '/local/templates/uuopera/includes/page_for_visitors.php',
            'extra_css' => ['tpl/css/page-beige.css'],
            'footer_js' => [],
        ]);
        return;
    }

    if ($doc !== '' && !defined('START_EXEC_PROLOG_BEFORE_2') && is_file($doc . '/bitrix/modules/main/include/prolog_before.php')) {
        require_once $doc . '/bitrix/modules/main/include/prolog_before.php';
    }

    $static = uuopera_cms_static_page_find($path);
    if ($static !== null) {
        $layout = uuopera_cms_static_page_layout($path);
        $GLOBALS['UUOPERA_CMS_STATIC_ID'] = $static['id'] ?? 0;
        $GLOBALS['UUOPERA_CMS_STATIC_TITLE'] = $static['title'];
        $GLOBALS['UUOPERA_CMS_STATIC_HTML'] = $static['html'];
        $GLOBALS['UUOPERA_CMS_STATIC_HEADER_SCHEMA'] = $static['header_schema'];
        $GLOBALS['UUOPERA_CMS_STATIC_WRAPPER_CLASS'] = $layout['wrapper_class'];
        $GLOBALS['UUOPERA_CMS_STATIC_HEADER_SCHEMA_ATTR'] = $layout['header_schema_attr'];
        uuopera_page([
            'title_callback' => 'uuopera_cms_static_page_apply_title',
            'include' => '/local/templates/uuopera/includes/page_static_cms.php',
            'extra_css' => $layout['extra_css'],
            'footer_js' => uuopera_cms_static_page_footer_js($path),
        ]);
        return;
    }

    if (preg_match('#^/category/([a-z0-9_-]+)$#', $path, $catM)) {
        $GLOBALS['UUOPERA_NEWS_CATEGORY_CODE'] = (string) $catM[1];
        uuopera_page([
            'title' => 'Новости - Бурятский театр оперы и балета',
            'include' => '/local/templates/uuopera/includes/page_news_category.php',
            'extra_css' => ['tpl/css/page-beige.css'],
            'footer_js' => [],
        ]);
        return;
    }

    $stubMap = [
        '/soglasie-na-obrabotku-personalnykh-d' => 'Согласие на обработку персональных данных',
        '/company/management' => 'Руководство',
        '/company/vacancies' => 'Вакансии',
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
