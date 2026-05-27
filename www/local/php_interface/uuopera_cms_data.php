<?php

declare(strict_types=1);

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

function uuopera_cms_iblock_prop_file_id(array $prop): int
{
    $v = $prop['VALUE'] ?? null;
    if ($v === null || $v === '' || $v === false) {
        return 0;
    }
    if (is_array($v)) {
        if ($v === []) {
            return 0;
        }
        $first = reset($v);
        return (int) $first;
    }
    return (int) $v;
}

function uuopera_cms_file_public_path(int $fileId): string
{
    if ($fileId <= 0) {
        return '';
    }
    $p = CFile::GetPath($fileId);
    return ($p !== false && $p !== '') ? (string) $p : '';
}

function uuopera_cms_static_pages_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'cms_static_pages_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

function uuopera_cms_home_slides_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'cms_home_slides_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

function uuopera_cms_projects_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'cms_projects_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

function uuopera_cms_about_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'cms_about_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

function uuopera_cms_service_faq_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'cms_service_faq_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

function uuopera_cms_contacts_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'cms_contacts_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

function uuopera_cms_prop_text_single(array $props, string $code): string
{
    $cell = $props[$code] ?? [];
    $v = $cell['VALUE'] ?? '';
    $v = $cell['~VALUE'] ?? $v;
    return is_array($v) ? trim(implode('', $v)) : trim((string) $v);
}

function uuopera_cms_normalize_request_path(string $path): string
{
    $path = strtok(str_replace('\\', '/', $path), '?') ?: $path;
    $path = str_replace('\\', '/', $path);
    $path = rtrim($path, '/');
    return $path === '' ? '/' : $path;
}

function uuopera_cms_static_page_fallback_html(string $requestPath): string
{
    $norm = uuopera_cms_normalize_request_path($requestPath);
    $files = [
        '/documents' => '_cms_documents_body.html',
        '/brandbook' => '_cms_brandbook_body.html',
    ];
    if (!isset($files[$norm])) {
        return '';
    }
    $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $file = $doc . '/local/templates/uuopera/includes/' . $files[$norm];
    if (!is_file($file)) {
        return '';
    }

    return uuopera_html_decode_content((string) file_get_contents($file));
}

function uuopera_cms_static_page_prepare_html(string $requestPath, string $html): string
{
    $norm = uuopera_cms_normalize_request_path($requestPath);
    if ($norm === '/brandbook' && trim($html) === '') {
        $fallback = uuopera_cms_static_page_fallback_html($requestPath);
        if ($fallback !== '') {
            return $fallback;
        }
    }
    if ($norm === '/documents') {
        if (trim($html) === '') {
            $fallback = uuopera_cms_static_page_fallback_html($requestPath);
            if ($fallback !== '') {
                return $fallback;
            }
        }

        return uuopera_html_prepare_documents_html($html);
    }

    return $html;
}

function uuopera_cms_static_page_footer_js(string $requestPath): array
{
    $norm = uuopera_cms_normalize_request_path($requestPath);
    if ($norm === '/documents') {
        return ['tpl/js/uuopera-documents-spoilers.js'];
    }

    return [];
}

/**
 * @return array{wrapper_class: string, extra_css: list<string>, header_schema_attr: bool}
 */
function uuopera_cms_static_page_layout(string $requestPath): array
{
    $norm = uuopera_cms_normalize_request_path($requestPath);
    if (in_array($norm, ['/documents', '/brandbook'], true)) {
        return [
            'wrapper_class'       => 'flex flex-col gap-16 2xl:gap-28 pt-32 wrapper-main wrapper-max',
            'extra_css'           => [],
            'header_schema_attr'  => $norm === '/brandbook',
        ];
    }

    return [
        'wrapper_class'       => 'wrapper-main wrapper-max py-24 md:py-32 text-p2',
        'extra_css'           => ['tpl/css/page-beige.css'],
        'header_schema_attr'  => true,
    ];
}

/**
 * @return array{title: string, html: string, header_schema: string}|null
 */
function uuopera_cms_static_page_find(string $requestPath): ?array
{
    $iblockId = uuopera_cms_static_pages_iblock_id();
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return null;
    }

    $norm = uuopera_cms_normalize_request_path($requestPath);
    $candidates = array_values(array_unique([$norm, $norm === '/' ? '/' : $norm . '/']));

    // Find element ID by REQUEST_PATH property value
    $propRes = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        [
            'IBLOCK_ID'              => $iblockId,
            'ACTIVE'                 => 'Y',
            'CHECK_PERMISSIONS'      => 'N',
            'PROPERTY_REQUEST_PATH'  => $candidates,
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'DETAIL_TEXT', 'PREVIEW_TEXT']
    );
    $row = $propRes ? $propRes->Fetch() : false;

    if (!is_array($row)) {
        return null;
    }

    $elId  = (int) ($row['ID'] ?? 0);
    $html  = (string) ($row['DETAIL_TEXT'] ?? '');
    if ($html === '' && !empty($row['PREVIEW_TEXT'])) {
        $html = (string) $row['PREVIEW_TEXT'];
    }

    // Fetch HEADER_SCHEMA property value
    $schema = 'beige';
    if ($elId > 0) {
        $schemaRes = CIBlockElement::GetProperty(
            $iblockId, $elId, [], ['CODE' => 'HEADER_SCHEMA']
        );
        if ($schemaRow = $schemaRes->Fetch()) {
            $sv = trim((string) ($schemaRow['VALUE'] ?? ''));
            if ($sv !== '') {
                $schema = $sv;
            }
        }
    }

    $html = uuopera_html_decode_content($html);
    $html = uuopera_cms_static_page_prepare_html($norm, $html);

    return [
        'id'            => $elId,
        'title'         => trim((string) ($row['NAME'] ?? '')) ?: 'Страница',
        'html'          => $html,
        'header_schema' => $schema,
    ];
}

/** @param CMain $APPLICATION */
function uuopera_cms_static_page_apply_title($APPLICATION): void
{
    $t = (string) ($GLOBALS['UUOPERA_CMS_STATIC_TITLE'] ?? '');
    $suffix = ' - Бурятский театр оперы и балета';
    if ($t === '') {
        $t = 'Страница';
    }
    $full = $t . $suffix;
    $APPLICATION->SetTitle($full);
    $APPLICATION->SetPageProperty('title', $full);
}

/**
 * @return list<array{
 *   name: string,
 *   link_url: string,
 *   subtext_html: string,
 *   age_mark: string,
 *   radario_afisha_key: string,
 *   intickets_url: string,
 *   image: string,
 *   srcset: string
 * }>
 */
function uuopera_cms_home_slides_list(): array
{
    $iblockId = uuopera_cms_home_slides_iblock_id();
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }

    // Первый проход — собираем ID и поля элементов
    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME', 'PREVIEW_PICTURE']
    );

    $rows = [];
    while ($row = $res->Fetch()) {
        $rows[(int) $row['ID']] = $row;
    }

    if (empty($rows)) {
        return [];
    }

    // Читаем свойства отдельным запросом (надёжнее GetNextElement->GetProperties)
    $propVals = [];
    CIBlockElement::GetPropertyValuesArray(
        $propVals,
        $iblockId,
        ['ID' => array_keys($rows), 'IBLOCK_ID' => $iblockId]
    );

    $strVal = static function ($prop): string {
        if (!is_array($prop)) {
            return '';
        }
        $v = $prop['VALUE'] ?? '';
        if (is_array($v)) {
            // TEXT/TYPE свойство
            if (isset($v['TEXT'])) {
                return trim((string) $v['TEXT']);
            }
            return trim((string) ($v[0] ?? ''));
        }
        return trim((string) $v);
    };

    $out = [];
    foreach ($rows as $elId => $f) {
        $p = $propVals[$elId] ?? [];

        $fid = (int) ($f['PREVIEW_PICTURE'] ?? 0);
        $img = '';
        $srcset = '';
        if ($fid > 0) {
            $path = CFile::GetPath($fid);
            if ($path !== false && $path !== '') {
                $img = (string) $path;
            }
            $srcset = uuopera_afisha_hero_srcset_from_file_id($fid);
        }

        $videoUrl = '';
        $videoPortraitUrl = '';
        $vidFid = (int) ($strVal($p['VIDEO_MP4'] ?? null));
        if ($vidFid > 0) {
            $vp = CFile::GetPath($vidFid);
            if ($vp !== false && $vp !== '') {
                $videoUrl = (string) $vp;
            }
        }
        $vidPFid = (int) ($strVal($p['VIDEO_MP4_PORTRAIT'] ?? null));
        if ($vidPFid > 0) {
            $vpp = CFile::GetPath($vidPFid);
            if ($vpp !== false && $vpp !== '') {
                $videoPortraitUrl = (string) $vpp;
            }
        }

        $out[] = [
            'name'               => trim((string) ($f['NAME'] ?? '')),
            'link_url'           => $strVal($p['LINK_URL'] ?? null) ?: '/afisha/',
            'subtext_html'       => uuopera_html_decode_content($strVal($p['SUBTEXT_HTML'] ?? null)),
            'age_mark'           => $strVal($p['AGE_MARK'] ?? null),
            'radario_afisha_key' => $strVal($p['RADARIO_AFISHA_KEY'] ?? null),
            'intickets_url'      => $strVal($p['INTICKETS_URL'] ?? null),
            'image'              => $img,
            'srcset'             => $srcset,
            'video_url'          => $videoUrl,
            'video_portrait_url' => $videoPortraitUrl,
        ];
    }

    return $out;
}

/**
 * @return list<array{
 *   code: string,
 *   name: string,
 *   url: string,
 *   teaser_html: string,
 *   image: string,
 *   srcset: string
 * }>
 */
function uuopera_cms_projects_list(): array
{
    $iblockId = uuopera_cms_projects_iblock_id();
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE']
    );

    $rows = [];
    while ($row = $res->Fetch()) {
        $code = trim((string) ($row['CODE'] ?? ''));
        if ($code !== '') {
            $rows[(int) $row['ID']] = $row;
        }
    }

    if (empty($rows)) {
        return [];
    }

    $rawProps = [];
    CIBlockElement::GetPropertyValuesArray($rawProps, $iblockId, ['ID' => array_keys($rows)], false);

    $getProp = static function (array $rawProps, int $elId, string $code): string {
        $v = $rawProps[$elId][$code] ?? null;
        if ($v === null || $v === false) {
            return '';
        }
        if (is_array($v) && isset($v['TEXT'])) {
            return (string) $v['TEXT'];
        }
        if (is_array($v)) {
            return trim((string) ($v[0] ?? ''));
        }
        return trim((string) $v);
    };

    $out = [];
    foreach ($rows as $elId => $row) {
        $code = trim((string) ($row['CODE'] ?? ''));
        $listUrl = $getProp($rawProps, $elId, 'LIST_URL');
        $url = $listUrl !== '' ? $listUrl : '/projects/' . rawurlencode($code) . '/';

        $fid = (int) ($row['PREVIEW_PICTURE'] ?? 0);
        $img = '';
        $srcset = '';
        if ($fid > 0) {
            $path = CFile::GetPath($fid);
            if ($path !== false && $path !== '') {
                $img = (string) $path;
            }
            $srcset = uuopera_afisha_hero_srcset_from_file_id($fid);
        }

        $out[] = [
            'code' => $code,
            'name' => trim((string) ($row['NAME'] ?? '')),
            'url' => $url,
            'teaser_html' => uuopera_html_decode_content($getProp($rawProps, $elId, 'TEASER_HTML')),
            'image' => $img,
            'srcset' => $srcset,
        ];
    }

    return $out;
}

/**
 * @return array{
 *   name: string,
 *   detail_html: string,
 *   image: string,
 *   srcset: string
 * }|null
 */
function uuopera_cms_project_by_code(string $code): ?array
{
    $code = trim($code);
    if ($code === '' || !Loader::includeModule('iblock')) {
        return null;
    }
    $iblockId = uuopera_cms_projects_iblock_id();
    if ($iblockId <= 0) {
        return null;
    }

    $res = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            '=CODE' => $code,
            'ACTIVE' => 'Y',
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'DETAIL_TEXT', 'PREVIEW_PICTURE']
    );
    $row = $res->Fetch();
    if (!$row) {
        return null;
    }
    $fid = (int) ($row['PREVIEW_PICTURE'] ?? 0);
    $img = '';
    $srcset = '';
    if ($fid > 0) {
        $path = CFile::GetPath($fid);
        if ($path !== false && $path !== '') {
            $img = (string) $path;
        }
        $srcset = uuopera_afisha_hero_srcset_from_file_id($fid);
    }

    return [
        'name' => html_entity_decode(trim((string) ($row['NAME'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'detail_html' => uuopera_html_decode_content((string) ($row['DETAIL_TEXT'] ?? '')),
        'image' => $img,
        'srcset' => $srcset,
    ];
}

/** @param CMain $APPLICATION */
function uuopera_cms_project_apply_title($APPLICATION): void
{
    $code = (string) ($GLOBALS['UUOPERA_PROJECT_CODE'] ?? '');
    $suffix = ' - Бурятский театр оперы и балета';
    $name = 'Проект';
    if ($code !== '' && Loader::includeModule('iblock')) {
        $row = uuopera_cms_project_by_code($code);
        if ($row !== null && $row['name'] !== '') {
            $name = $row['name'];
        }
    }
    $full = $name . $suffix;
    $APPLICATION->SetTitle($full);
    $APPLICATION->SetPageProperty('title', $full);
}

function uuopera_about_get_data(): array
{
    $empty = ['timeline' => [], 'mission_slides' => [], 'html_blocks' => []];
    $iblockId = uuopera_cms_about_iblock_id();
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return $empty;
    }

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME']
    );

    $ids = [];
    while ($row = $res->Fetch()) {
        $ids[] = (int) $row['ID'];
    }
    if ($ids === []) {
        return $empty;
    }

    $propsMap = [];
    CIBlockElement::GetPropertyValuesArray($propsMap, $iblockId, ['ID' => $ids]);

    $strVal = static function (array $prop): string {
        $v = $prop['VALUE'] ?? '';
        return is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
    };
    $htmlVal = static function (array $prop): string {
        $v = $prop['VALUE'] ?? '';
        if (is_array($v) && isset($v['TEXT'])) {
            return uuopera_html_decode_content((string) $v['TEXT']);
        }
        if (is_array($v)) {
            return uuopera_html_decode_content(trim((string) ($v[0] ?? '')));
        }

        return uuopera_html_decode_content((string) $v);
    };

    $timeline = [];
    $mission = [];
    $htmlBlocks = [];

    foreach ($ids as $id) {
        $p = $propsMap[$id] ?? [];
        $kind = strtolower($strVal($p['BLOCK_KIND'] ?? []));
        $body = $htmlVal($p['BODY_HTML'] ?? []);

        if ($kind === 'timeline') {
            $sideSrc = uuopera_cms_file_public_path(uuopera_cms_iblock_prop_file_id($p['SIDE_IMAGE'] ?? []));
            if ($sideSrc === '') {
                $sideSrc = $strVal($p['SIDE_IMAGE_URL'] ?? []);
            }
            $timeline[] = [
                'year'         => $strVal($p['YEAR_LABEL'] ?? []),
                'title'        => $strVal($p['BLOCK_TITLE'] ?? []),
                'body_html'    => $body,
                'side_image'   => $sideSrc,
                'side_caption' => $strVal($p['SIDE_CAPTION'] ?? []),
            ];
        } elseif ($kind === 'mission') {
            $diag = uuopera_cms_file_public_path(uuopera_cms_iblock_prop_file_id($p['DIAGRAM_IMAGE'] ?? []));
            if ($diag === '') {
                $diag = $strVal($p['DIAGRAM_IMAGE_URL'] ?? []);
            }
            $theme = strtolower($strVal($p['THEME'] ?? []));
            if (!in_array($theme, ['white', 'brown', 'blue'], true)) {
                $theme = 'white';
            }
            $mission[] = [
                'theme'       => $theme,
                'lead'        => $strVal($p['BLOCK_TITLE'] ?? []),
                'body_html'   => $body,
                'diagram_src' => $diag,
            ];
        } elseif ($kind === 'html') {
            if ($body !== '') {
                $htmlBlocks[] = $body;
            }
        }
    }

    return [
        'timeline'      => $timeline,
        'mission_slides' => $mission,
        'html_blocks'   => $htmlBlocks,
    ];
}

/**
 * @return array{
 *   intro_html: string,
 *   files: list<array{name: string, url: string}>,
 *   items: list<array{name: string, description: string, contact_person: string, phone: string, email: string, image_url: string, description_extra: string}>
 * }
 */
function uuopera_services_read_iblock(): array
{
    $iblockId = uuopera_cms_service_faq_iblock_id();
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return ['intro_html' => '', 'files' => [], 'items' => []];
    }

    $decode = static function (string $v): string {
        return uuopera_html_decode_content($v);
    };

    // Read a single string property via GetProperty() — works in Bitrix demo mode
    // (GetProperties() on CIBlockElement object returns null in demo mode)
    $getProp = static function (int $iblockId, int $elId, string $code) use ($decode): string {
        $r = CIBlockElement::GetProperty($iblockId, $elId, [], ['CODE' => $code]);
        $row = $r->Fetch();
        if (!$row) {
            return '';
        }
        $v = $row['VALUE'] ?? '';
        if ($row['PROPERTY_TYPE'] === 'T' && is_array($v)) {
            return (string) ($v['TEXT'] ?? '');
        }
        return $decode(trim((string) $v));
    };

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME', 'DETAIL_TEXT']
    );

    $introHtml = '';
    $files = [];
    $items = [];

    while ($row = $res->Fetch()) {
        $elId = (int) $row['ID'];
        $name = $decode(trim((string) ($row['NAME'] ?? '')));
        $type = strtolower($getProp($iblockId, $elId, 'ELEMENT_TYPE'));

        if ($type === 'intro') {
            $introHtml = uuopera_html_decode_content((string) ($row['DETAIL_TEXT'] ?? ''));
        } elseif ($type === 'file') {
            $url = $getProp($iblockId, $elId, 'FILE_URL');
            if ($url !== '') {
                $labelHtml = nl2br(htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $files[] = ['name' => $labelHtml, 'url' => $url];
            }
        } else {
            $items[] = [
                'name'              => $name,
                'description'       => uuopera_html_decode_content((string) ($row['DETAIL_TEXT'] ?? '')),
                'contact_person'    => $getProp($iblockId, $elId, 'CONTACT_PERSON'),
                'phone'             => $getProp($iblockId, $elId, 'PHONE'),
                'email'             => $getProp($iblockId, $elId, 'EMAIL'),
                'image_url'         => $getProp($iblockId, $elId, 'IMAGE_URL'),
                'description_extra' => $getProp($iblockId, $elId, 'DESCRIPTION_EXTRA'),
            ];
        }
    }

    return ['intro_html' => $introHtml, 'files' => $files, 'items' => $items];
}

/**
 * @return array{
 *   address_html: string,
 *   grid_html: string,
 *   map_latlng: string,
 *   map_api_key: string,
 *   feedback_image: string,
 *   form_action: string
 * }
 */
function uuopera_contacts_get_data(): array
{
    $defaults = [
        'address_html' => "Республика Бурятия,<br>Улан-Удэ, Ленина 51",
        'grid_html' => <<<'HTML'
<div class="flex flex-col">
    <a href="tel:+73012213600">8 (3012) 21-36-00 Касса режим работы с 10:00 до 19:00</a>
    <a href="mailto:uuopera@govrb.ru">uuopera@govrb.ru</a>
</div>
<div class="flex flex-col">
    <div>Приемная </div>
    <div>8 (3012) 21-44-54</div>
</div>
<div class="flex flex-col">
    <div>Отдел продаж (режим работы пн-пт с  09:00 до 18:00)</div>
    <div>8 (3012) 21-89-81</div>
</div>
<div class="flex flex-col">
    <div>Отдел кадров (режим работы пн-пт с  08:30 до 17:30)</div>
    <div>8 (3012) 21-73-49</div>
</div>
<div class="flex flex-col">
    <div>Рекламный отдел (режим работы пн-пт с  09:00 до 18:00)</div>
    <div>8 (3012) 21-55-05</div>
</div>
HTML,
        'map_latlng' => '51.832861, 107.583442',
        'map_api_key' => 'ec538965-b772-4b72-940b-760238e42731',
        'feedback_image' => 'https://uuopera.ru/wp-content/themes/uuopera/images/feedback.jpg',
        'form_action' => '/wp-json/uuopera/v1/feedback/',
    ];

    $pick = static function (string $iblockHtml, string $optKey, string $defHtml) use ($defaults): string {
        $trimI = trim($iblockHtml);
        if ($trimI !== '') {
            return $iblockHtml;
        }
        if (!class_exists(Option::class)) {
            return $defHtml;
        }
        $vo = trim((string) Option::get('uuopera', $optKey, ''));
        return $vo !== '' ? $vo : $defHtml;
    };

    $pickLine = static function (string $iblockVal, string $optKey, string $defLine) use ($defaults): string {
        $trimI = trim($iblockVal);
        if ($trimI !== '') {
            return $trimI;
        }
        if (!class_exists(Option::class)) {
            return $defLine;
        }
        $vo = trim((string) Option::get('uuopera', $optKey, ''));
        return $vo !== '' ? $vo : $defLine;
    };

    $ibProps = [];
    $iblockId = uuopera_cms_contacts_iblock_id();
    if ($iblockId > 0 && Loader::includeModule('iblock')) {
        $res = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID']
        );
        if ($ob = $res->GetNextElement()) {
            $ibProps = $ob->GetProperties();
        }
    }

    $addressI = $ibProps === [] ? '' : uuopera_cms_prop_text_single($ibProps, 'ADDRESS_HTML');
    $gridI = $ibProps === [] ? '' : uuopera_cms_prop_text_single($ibProps, 'GRID_HTML');
    $latI = $ibProps === [] ? '' : uuopera_cms_prop_text_single($ibProps, 'MAP_LATLNG');
    $keyI = $ibProps === [] ? '' : uuopera_cms_prop_text_single($ibProps, 'MAP_API_KEY');
    $formI = $ibProps === [] ? '' : uuopera_cms_prop_text_single($ibProps, 'FORM_ACTION');
    $fbPathRel = '';
    $fbUrlProp = '';
    if ($ibProps !== []) {
        $fid = uuopera_cms_iblock_prop_file_id($ibProps['FEEDBACK_IMAGE'] ?? []);
        $fbPathRel = $fid > 0 ? uuopera_cms_file_public_path($fid) : '';
        $fbUrlProp = uuopera_cms_prop_text_single($ibProps, 'FEEDBACK_IMAGE_URL');
    }
    $feedback = trim((string) $fbPathRel);
    if ($feedback !== '' && ($feedback[0] ?? '') !== '/') {
        $feedback = '/' . ltrim($feedback, '/');
    }
    if ($feedback === '') {
        $feedback = trim($fbUrlProp);
    }
    if ($feedback === '') {
        $feedback = $pickLine('', 'contacts_feedback_image', $defaults['feedback_image']);
    }

    return [
        'address_html' => uuopera_html_decode_content($pick($addressI, 'contacts_address_html', $defaults['address_html'])),
        'grid_html' => uuopera_html_decode_content($pick($gridI, 'contacts_grid_html', $defaults['grid_html'])),
        'map_latlng' => $pickLine($latI, 'contacts_map_latlng', $defaults['map_latlng']),
        'map_api_key' => $pickLine($keyI, 'contacts_map_api_key', $defaults['map_api_key']),
        'feedback_image' => $feedback,
        'form_action' => $pickLine($formI, 'contacts_form_action', $defaults['form_action']),
    ];
}

/**
 * @return array{
 *   intro_html: string,
 *   files: list<array{name: string, url: string}>,
 *   items: list<array{name: string, description: string, contact_person: string, phone: string, email: string, image_url: string, description_extra: string}>
 * }
 */
function uuopera_services_get_data(): array
{
    return uuopera_services_read_iblock();
}

function uuopera_persone_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'persone_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

/**
 * @return list<array{id: int, name: string, slug: string, role: string, photo: string, photo_url: string}>
 */
/**
 * Returns persons for a category, grouped by their GROUPS property values.
 * Each person can appear in multiple groups.
 * Returns: ['group_name' => [person, ...], ...]  (ordered by first appearance)
 * Falls back to grouping by ROLE if GROUPS is empty.
 *
 * @return array<string, list<array{id:int,name:string,slug:string,role:string,photo:string,photo_url:string}>>
 */
function uuopera_persone_list_by_category(string $category): array
{
    $category = trim($category);
    if ($category === '' || !Loader::includeModule('iblock')) {
        return [];
    }
    $iblockId = uuopera_persone_iblock_id();
    if ($iblockId <= 0) {
        return [];
    }

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'NAME' => 'ASC'],
        [
            'IBLOCK_ID'         => $iblockId,
            'ACTIVE'            => 'Y',
            'CHECK_PERMISSIONS' => 'N',
            'PROPERTY_CATEGORY' => $category,
        ],
        false,
        false,
        ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE']
    );

    $rows = [];
    while ($row = $res->Fetch()) {
        $rows[(int) $row['ID']] = $row;
    }
    if (empty($rows)) {
        return [];
    }

    $rawProps = [];
    CIBlockElement::GetPropertyValuesArray($rawProps, $iblockId, ['ID' => array_keys($rows)], false);

    /** @var array<string, list<array{id:int,name:string,slug:string,role:string,photo:string,photo_url:string}>> $grouped */
    $grouped = [];

    foreach ($rows as $elId => $row) {
        $slug = trim((string) ($row['CODE'] ?? ''));
        $name = html_entity_decode(trim((string) ($row['NAME'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $rawRole = $rawProps[$elId]['ROLE'] ?? '';
        $role = is_array($rawRole) ? trim((string) ($rawRole['VALUE'] ?? '')) : trim((string) $rawRole);

        $rawPhotoUrl = $rawProps[$elId]['PHOTO_URL'] ?? '';
        $photoUrl = is_array($rawPhotoUrl) ? trim((string) ($rawPhotoUrl['VALUE'] ?? '')) : trim((string) $rawPhotoUrl);

        $fid = (int) ($row['PREVIEW_PICTURE'] ?? 0);
        $photo = '';
        if ($fid > 0) {
            $p = CFile::GetPath($fid);
            if ($p !== false && $p !== '') {
                $photo = (string) $p;
            }
        }
        if ($photo === '' && $photoUrl !== '') {
            $photo = $photoUrl;
        }

        // Collect sub-groups from GROUPS property (MULTIPLE='Y' → values in ['VALUE'] array)
        $groupNames = [];
        $rawGroups = $rawProps[$elId]['GROUPS'] ?? null;
        if (is_array($rawGroups) && isset($rawGroups['VALUE'])) {
            foreach ((array) $rawGroups['VALUE'] as $gv) {
                $gn = trim((string) $gv);
                if ($gn !== '') {
                    $groupNames[] = $gn;
                }
            }
        }
        if (empty($groupNames) && $role !== '') {
            $groupNames = [$role];
        }

        $person = [
            'id'        => $elId,
            'name'      => $name,
            'slug'      => $slug,
            'role'      => $role,
            'photo'     => $photo,
            'photo_url' => $photoUrl,
        ];

        foreach ($groupNames as $gn) {
            $grouped[$gn][] = $person;
        }
    }

    return $grouped;
}

/**
 * @return array{name: string, role: string, photo: string, detail_html: string}|null
 */
function uuopera_persone_by_slug(string $slug): ?array
{
    $slug = trim($slug);
    if ($slug === '' || !Loader::includeModule('iblock')) {
        return null;
    }
    $iblockId = uuopera_persone_iblock_id();
    if ($iblockId <= 0) {
        return null;
    }

    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=CODE' => $slug, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE', 'DETAIL_TEXT']
    );
    $row = $res->Fetch();
    if (!$row) {
        return null;
    }
    $elId = (int) $row['ID'];

    $rawProps = [];
    CIBlockElement::GetPropertyValuesArray($rawProps, $iblockId, ['ID' => [$elId]], false);

    $rawRole = $rawProps[$elId]['ROLE'] ?? '';
    $role = is_array($rawRole) ? trim((string) ($rawRole['VALUE'] ?? '')) : trim((string) $rawRole);

    $rawPhotoUrl = $rawProps[$elId]['PHOTO_URL'] ?? '';
    $photoUrl = is_array($rawPhotoUrl) ? trim((string) ($rawPhotoUrl['VALUE'] ?? '')) : trim((string) $rawPhotoUrl);

    $fid = (int) ($row['PREVIEW_PICTURE'] ?? 0);
    $photo = '';
    if ($fid > 0) {
        $p = CFile::GetPath($fid);
        if ($p !== false && $p !== '') {
            $photo = (string) $p;
        }
    }
    if ($photo === '' && $photoUrl !== '') {
        $photo = $photoUrl;
    }

    return [
        'name'        => html_entity_decode(trim((string) ($row['NAME'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
        'role'        => $role,
        'photo'       => $photo,
        'detail_html' => uuopera_html_decode_content((string) ($row['DETAIL_TEXT'] ?? '')),
    ];
}

function uuopera_persone_slug_matches_url(string $url, string $slug): bool
{
    $slug = trim($slug);
    if ($slug === '') {
        return false;
    }
    $url = strtolower(trim($url));
    if ($url === '') {
        return false;
    }
    $encoded = rawurlencode($slug);
    $candidates = [
        '/persone/' . $slug . '/',
        '/persone/' . $slug,
        '/persone/' . $encoded . '/',
        '/persone/' . $encoded,
    ];

    return in_array($url, $candidates, true)
        || str_contains($url, '/persone/' . $slug)
        || str_contains($url, '/persone/' . $encoded);
}

/**
 * @param array{label: string, event_id: int, sql_dt: string} $session
 */
function uuopera_persone_session_is_upcoming(array $session): bool
{
    $sqlDt = trim((string) ($session['sql_dt'] ?? ''));
    if ($sqlDt !== '') {
        $ts = strtotime($sqlDt);

        return $ts !== false && $ts >= time();
    }

    static $monthMap = [
        'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
        'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
        'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12,
    ];
    $label = trim((string) ($session['label'] ?? ''));
    if ($label === '') {
        return false;
    }
    if (!preg_match('/^(\d{1,2})\s+(' . implode('|', array_keys($monthMap)) . ')(?:\s+(\d{2}:\d{2}))?/u', $label, $m)) {
        return true;
    }
    $day = (int) $m[1];
    $monthNum = $monthMap[$m[2]] ?? 0;
    $timeParts = isset($m[3]) ? explode(':', $m[3]) : ['0', '0'];
    $year = (int) date('Y');
    $ts = mktime((int) $timeParts[0], (int) ($timeParts[1] ?? 0), 0, $monthNum, $day, $year);
    if ($ts < time()) {
        $ts = mktime((int) $timeParts[0], (int) ($timeParts[1] ?? 0), 0, $monthNum, $day, $year + 1);
    }

    return $ts >= time();
}

/**
 * События афиши, в составе которых участвует персона (как на uuopera.ru/persone/{slug}/).
 *
 * @return list<array{
 *   url: string,
 *   name: string,
 *   date_labels: list<string>,
 *   roles: list<string>,
 *   hero_image: string,
 *   hero_srcset: string,
 *   sort_ts: int
 * }>
 */
function uuopera_persone_afisha_events_for_slug(string $slug): array
{
    $slug = trim($slug);
    if ($slug === '' || !Loader::includeModule('iblock')) {
        return [];
    }
    $iblockId = uuopera_afisha_events_iblock_id();
    if ($iblockId <= 0) {
        return [];
    }

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE', 'IBLOCK_SECTION_ID']
    );

    $out = [];
    while ($row = $res->Fetch()) {
        $elementId = (int) ($row['ID'] ?? 0);
        $code = trim((string) ($row['CODE'] ?? ''));
        if ($elementId <= 0 || $code === '') {
            continue;
        }

        $castByDate = uuopera_afisha_admin_cast_load_map_raw($iblockId, $elementId);
        if ($castByDate === []) {
            continue;
        }

        $rolesByDate = [];
        $undatedRoles = [];
        foreach ($castByDate as $sqlDt => $cast) {
            $matchedRoles = [];
            foreach ($cast as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                if (!uuopera_persone_slug_matches_url((string) ($entry['url'] ?? ''), $slug)) {
                    continue;
                }
                $role = trim((string) ($entry['role'] ?? ''));
                if ($role === '' || mb_strtolower($role, 'UTF-8') === 'состав') {
                    continue;
                }
                $matchedRoles[] = $role;
            }
            if ($matchedRoles === []) {
                continue;
            }
            $matchedRoles = array_values(array_unique($matchedRoles));
            if ((string) $sqlDt === '') {
                $undatedRoles = array_values(array_unique(array_merge($undatedRoles, $matchedRoles)));
            } else {
                $rolesByDate[(string) $sqlDt] = $matchedRoles;
            }
        }

        if ($rolesByDate === [] && $undatedRoles === []) {
            continue;
        }

        $sessions = uuopera_afisha_admin_parse_sessions(
            uuopera_afisha_admin_read_prop($iblockId, $elementId, 'SESSIONS_JSON')
        );

        $dateLabels = [];
        $sortTs = PHP_INT_MAX;
        $roles = [];

        if ($undatedRoles !== []) {
            $roles = $undatedRoles;
            foreach ($sessions as $session) {
                if (!uuopera_persone_session_is_upcoming($session)) {
                    continue;
                }
                $label = trim((string) ($session['label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $dateLabels[] = $label;
                $sqlDt = trim((string) ($session['sql_dt'] ?? ''));
                $ts = $sqlDt !== '' ? strtotime($sqlDt) : false;
                if ($ts !== false && $ts < $sortTs) {
                    $sortTs = (int) $ts;
                }
            }
        } else {
            foreach ($sessions as $session) {
                $sqlDt = trim((string) ($session['sql_dt'] ?? ''));
                if ($sqlDt === '' || !isset($rolesByDate[$sqlDt])) {
                    continue;
                }
                if (!uuopera_persone_session_is_upcoming($session)) {
                    continue;
                }
                foreach ($rolesByDate[$sqlDt] as $r) {
                    $roles[] = $r;
                }
                $label = trim((string) ($session['label'] ?? ''));
                if ($label === '') {
                    $label = uuopera_afisha_admin_cast_label($sqlDt);
                }
                $dateLabels[] = $label;
                $ts = strtotime($sqlDt);
                if ($ts !== false && $ts < $sortTs) {
                    $sortTs = (int) $ts;
                }
            }
            $roles = array_values(array_unique($roles));
        }

        $dateLabels = array_values(array_unique($dateLabels));
        if ($dateLabels === []) {
            continue;
        }

        $propsRes = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'CATEGORY']);
        $category = '';
        if ($propRow = $propsRes->Fetch()) {
            $category = trim((string) ($propRow['VALUE'] ?? ''));
        }
        $sectionId = (int) ($row['IBLOCK_SECTION_ID'] ?? 0);
        $catSlug = uuopera_afisha_resolve_listing_category_slug(
            ['CATEGORY' => ['VALUE' => $category]],
            $code,
            $sectionId,
            $iblockId
        );

        $previewId = (int) ($row['PREVIEW_PICTURE'] ?? 0);
        $heroImage = '';
        $heroSrcset = '';
        if ($previewId > 0) {
            $path = CFile::GetPath($previewId);
            if ($path !== false && $path !== '') {
                $heroImage = (string) $path;
            }
            $heroSrcset = uuopera_afisha_hero_srcset_from_file_id($previewId);
        }

        $out[] = [
            'url' => '/afisha/' . rawurlencode($catSlug) . '/' . rawurlencode($code) . '/',
            'name' => trim((string) ($row['NAME'] ?? '')),
            'date_labels' => $dateLabels,
            'roles' => $roles,
            'hero_image' => $heroImage,
            'hero_srcset' => $heroSrcset,
            'sort_ts' => $sortTs === PHP_INT_MAX ? 0 : $sortTs,
        ];
    }

    usort($out, static function (array $a, array $b): int {
        $ta = (int) ($a['sort_ts'] ?? 0);
        $tb = (int) ($b['sort_ts'] ?? 0);
        if ($ta === $tb) {
            return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        }

        return $ta <=> $tb;
    });

    return $out;
}

/** @param CMain $APPLICATION */
function uuopera_persone_apply_title($APPLICATION): void
{
    $slug = (string) ($GLOBALS['UUOPERA_PERSONE_SLUG'] ?? '');
    $suffix = ' - Бурятский театр оперы и балета';
    $name = 'Персона';
    if ($slug !== '') {
        $p = uuopera_persone_by_slug($slug);
        if ($p !== null && $p['name'] !== '') {
            $name = $p['name'];
        }
    }
    $full = $name . $suffix;
    $APPLICATION->SetTitle($full);
    $APPLICATION->SetPageProperty('title', $full);
}
