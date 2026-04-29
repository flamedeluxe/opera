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

function uuopera_cms_normalize_request_path(string $path): string
{
    $path = strtok(str_replace('\\', '/', $path), '?') ?: $path;
    $path = str_replace('\\', '/', $path);
    $path = rtrim($path, '/');
    return $path === '' ? '/' : $path;
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

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
        false,
        false,
        ['ID', 'NAME', 'DETAIL_TEXT', 'PREVIEW_TEXT']
    );
    while ($ob = $res->GetNextElement()) {
        $fields = $ob->GetFields();
        $props = $ob->GetProperties([], ['REQUEST_PATH', 'HEADER_SCHEMA']);
        $rp = $props['REQUEST_PATH'] ?? [];
        $rv = $rp['VALUE'] ?? '';
        $pathVal = is_array($rv) ? trim((string) ($rv[0] ?? '')) : trim((string) $rv);
        $pathNorm = uuopera_cms_normalize_request_path($pathVal);
        if ($pathNorm === '') {
            continue;
        }
        $match = false;
        foreach ($candidates as $cand) {
            $cn = uuopera_cms_normalize_request_path($cand);
            if ($pathNorm === $cn) {
                $match = true;
                break;
            }
        }
        if (!$match) {
            continue;
        }
        $html = (string) ($fields['DETAIL_TEXT'] ?? '');
        if ($html === '' && !empty($fields['PREVIEW_TEXT'])) {
            $html = (string) $fields['PREVIEW_TEXT'];
        }
        $p = $props['HEADER_SCHEMA'] ?? [];
        $v = $p['VALUE'] ?? '';
        $schema = is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
        if ($schema === '') {
            $schema = 'beige';
        }

        return [
            'title' => trim((string) ($fields['NAME'] ?? '')) ?: 'Страница',
            'html' => $html,
            'header_schema' => $schema,
        ];
    }

    return null;
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

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
        false,
        false,
        ['ID', 'NAME', 'PREVIEW_PICTURE']
    );

    $out = [];
    while ($ob = $res->GetNextElement()) {
        $f = $ob->GetFields();
        $p = $ob->GetProperties();
        $str = static function (array $prop): string {
            $v = $prop['VALUE'] ?? '';
            return is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
        };
        $sub = '';
        $pv = $p['SUBTEXT_HTML'] ?? [];
        $sv = $pv['VALUE'] ?? '';
        if (is_array($sv) && isset($sv['TEXT'])) {
            $sub = (string) $sv['TEXT'];
        } elseif (is_string($sv)) {
            $sub = $sv;
        }

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

        $out[] = [
            'name' => trim((string) ($f['NAME'] ?? '')),
            'link_url' => $str($p['LINK_URL'] ?? []) ?: '/afisha/',
            'subtext_html' => $sub,
            'age_mark' => $str($p['AGE_MARK'] ?? []),
            'radario_afisha_key' => $str($p['RADARIO_AFISHA_KEY'] ?? []),
            'intickets_url' => $str($p['INTICKETS_URL'] ?? []),
            'image' => $img,
            'srcset' => $srcset,
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
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
        false,
        false,
        ['ID', 'NAME', 'CODE', 'PREVIEW_PICTURE']
    );

    $out = [];
    while ($ob = $res->GetNextElement()) {
        $f = $ob->GetFields();
        $p = $ob->GetProperties();
        $code = trim((string) ($f['CODE'] ?? ''));
        if ($code === '') {
            continue;
        }
        $str = static function (array $prop): string {
            $v = $prop['VALUE'] ?? '';
            if (is_array($v) && isset($v['TEXT'])) {
                return (string) $v['TEXT'];
            }
            return is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
        };
        $listUrl = $str($p['LIST_URL'] ?? []);
        $url = $listUrl !== '' ? $listUrl : '/projects/' . rawurlencode($code) . '/';

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

        $out[] = [
            'code' => $code,
            'name' => trim((string) ($f['NAME'] ?? '')),
            'url' => $url,
            'teaser_html' => $str($p['TEASER_HTML'] ?? []),
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
            'CHECK_PERMISSIONS' => 'Y',
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'DETAIL_TEXT', 'PREVIEW_PICTURE']
    );
    $ob = $res->GetNextElement();
    if (!$ob) {
        return null;
    }
    $f = $ob->GetFields();
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

    return [
        'name' => trim((string) ($f['NAME'] ?? '')),
        'detail_html' => (string) ($f['DETAIL_TEXT'] ?? ''),
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

/**
 * @return array{
 *   timeline: list<array{year: string, title: string, body_html: string, side_image: string}>,
 *   mission_slides: list<array{theme: string, lead: string, body_html: string, diagram_src: string}>,
 *   html_blocks: list<string>
 * }
 */
function uuopera_about_get_data(): array
{
    $empty = ['timeline' => [], 'mission_slides' => [], 'html_blocks' => []];
    $iblockId = uuopera_cms_about_iblock_id();
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return $empty;
    }

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
        false,
        false,
        ['ID', 'NAME', 'PREVIEW_TEXT']
    );

    $str = static function (array $prop): string {
        $v = $prop['VALUE'] ?? '';
        return is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
    };
    $htmlProp = static function (array $prop): string {
        $v = $prop['VALUE'] ?? '';
        if (is_array($v) && isset($v['TEXT'])) {
            return (string) $v['TEXT'];
        }
        if (is_array($v)) {
            return trim((string) ($v[0] ?? ''));
        }
        return (string) $v;
    };

    $timeline = [];
    $mission = [];
    $htmlBlocks = [];

    while ($ob = $res->GetNextElement()) {
        $p = $ob->GetProperties();
        $kind = strtolower($str($p['BLOCK_KIND'] ?? []));
        $body = $htmlProp($p['BODY_HTML'] ?? []);

        if ($kind === 'timeline') {
            $sideSrc = uuopera_cms_file_public_path(uuopera_cms_iblock_prop_file_id($p['SIDE_IMAGE'] ?? []));
            $timeline[] = [
                'year' => $str($p['YEAR_LABEL'] ?? []),
                'title' => $str($p['BLOCK_TITLE'] ?? []),
                'body_html' => $body,
                'side_image' => $sideSrc,
            ];
        } elseif ($kind === 'mission') {
            $diag = uuopera_cms_file_public_path(uuopera_cms_iblock_prop_file_id($p['DIAGRAM_IMAGE'] ?? []));
            $theme = strtolower($str($p['THEME'] ?? []));
            if (!in_array($theme, ['white', 'brown', 'blue'], true)) {
                $theme = 'white';
            }
            $lead = $str($p['BLOCK_TITLE'] ?? []);
            $mission[] = [
                'theme' => $theme,
                'lead' => $lead,
                'body_html' => $body,
                'diagram_src' => $diag,
            ];
        } elseif ($kind === 'html') {
            if ($body !== '') {
                $htmlBlocks[] = $body;
            }
        }
    }

    return [
        'timeline' => $timeline,
        'mission_slides' => $mission,
        'html_blocks' => $htmlBlocks,
    ];
}

/**
 * @return list<array{question: string, answer_html: string}>
 */
function uuopera_service_faq_list(): array
{
    $iblockId = uuopera_cms_service_faq_iblock_id();
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
        false,
        false,
        ['ID', 'NAME']
    );

    $out = [];
    while ($ob = $res->GetNextElement()) {
        $f = $ob->GetFields();
        $p = $ob->GetProperties();
        $v = $p['ANSWER_HTML']['VALUE'] ?? '';
        $html = '';
        if (is_array($v) && isset($v['TEXT'])) {
            $html = (string) $v['TEXT'];
        } elseif (is_string($v)) {
            $html = $v;
        }
        $out[] = [
            'question' => trim((string) ($f['NAME'] ?? '')),
            'answer_html' => $html,
        ];
    }

    return $out;
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

    if (!class_exists(Option::class)) {
        return $defaults;
    }

    $g = static function (string $key, string $fallback) use ($defaults): string {
        $v = trim((string) Option::get('uuopera', $key, ''));
        return $v !== '' ? $v : ($defaults[$key] ?? $fallback);
    };

    return [
        'address_html' => $g('contacts_address_html', $defaults['address_html']),
        'grid_html' => $g('contacts_grid_html', $defaults['grid_html']),
        'map_latlng' => $g('contacts_map_latlng', $defaults['map_latlng']),
        'map_api_key' => $g('contacts_map_api_key', $defaults['map_api_key']),
        'feedback_image' => $g('contacts_feedback_image', $defaults['feedback_image']),
        'form_action' => $g('contacts_form_action', $defaults['form_action']),
    ];
}

/**
 * @return array{intro_html: string, pdf_regulation_url: string, pdf_price_url: string}
 */
function uuopera_services_get_data(): array
{
    $defaults = [
        'intro_html' => '<p>В нашем театре вы можете провести красивую фотосессию, арендовать сцену или холл для репетиций, взять в аренду высококлассное оборудование. Мы рады быть открытыми и полезными для наших гостей и партнеров</p>',
        'pdf_regulation_url' => 'https://uuopera.ru/wp-content/uploads/2024/12/polozhenie-o-platnykh-aprel-2022.pdf',
        'pdf_price_url' => 'https://uuopera.ru/wp-content/uploads/2026/03/prejskurant-cen-1.pdf',
    ];

    if (!class_exists(Option::class)) {
        return $defaults;
    }

    $g = static function (string $key, string $fallback) use ($defaults): string {
        $v = trim((string) Option::get('uuopera', $key, ''));
        return $v !== '' ? $v : ($defaults[$key] ?? $fallback);
    };

    return [
        'intro_html' => $g('services_intro_html', $defaults['intro_html']),
        'pdf_regulation_url' => $g('services_pdf_regulation_url', $defaults['pdf_regulation_url']),
        'pdf_price_url' => $g('services_pdf_price_url', $defaults['pdf_price_url']),
    ];
}
