<?php

declare(strict_types=1);

/**
 * Публичная страница /afisha/{категория}/{код}/ из инфоблока uuopera_afisha_events.
 */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

function uuopera_afisha_events_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'afisha_events_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

/**
 * @return array<string, mixed>
 */
function uuopera_afisha_event_empty_data(): array
{
    return [
        'name' => '',
        'layout' => 'event',
        'category' => '',
        'age' => '',
        'hero_image' => '',
        'hero_srcset' => '',
        'hero_meta_html' => '',
        'radario_hero_mode' => '',
        'radario_afisha_key' => '',
        'radario_hero_event_id' => 0,
        'sessions' => [],
        'participants_html' => '',
        'description_html' => '',
        'content_html' => '',
        'footer_duration' => '',
        'footer_price' => '',
        'slider_id' => 'slider',
        'gallery' => [],
        'pushkin_card' => false,
    ];
}

/** @param CMain $APPLICATION */
function uuopera_afisha_event_apply_title($APPLICATION): void
{
    $code = (string) ($GLOBALS['UUOPERA_AFISHA_CODE'] ?? '');
    $suffix = ' - Бурятский театр оперы и балета';
    $fallback = 'Событие' . $suffix;

    if ($code === '' || !Loader::includeModule('iblock')) {
        $APPLICATION->SetTitle($fallback);
        $APPLICATION->SetPageProperty('title', $fallback);
        return;
    }

    $iblockId = uuopera_afisha_events_iblock_id();
    if ($iblockId <= 0) {
        $APPLICATION->SetTitle($fallback);
        $APPLICATION->SetPageProperty('title', $fallback);
        return;
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
        ['NAME']
    );
    if ($row = $res->Fetch()) {
        $name = trim((string) $row['NAME']);
        if ($name === '') {
            $name = 'Событие';
        }
        $full = $name . $suffix;
        $APPLICATION->SetTitle($full);
        $APPLICATION->SetPageProperty('title', $full);
        return;
    }

    $APPLICATION->SetTitle($fallback);
    $APPLICATION->SetPageProperty('title', $fallback);
}

function uuopera_afisha_prop_text_html(array $prop): string
{
    $v = $prop['VALUE'] ?? '';
    if (is_array($v) && isset($v['TEXT'])) {
        return (string) $v['TEXT'];
    }
    if (is_array($v)) {
        return (string) ($v[0] ?? '');
    }
    return (string) $v;
}

/** Строковые свойства и JSON в полях типа «текст» (VALUE = ['TEXT' => …]). */
function uuopera_afisha_prop_value_plain(array $prop): string
{
    $v = $prop['VALUE'] ?? '';
    if (is_array($v) && array_key_exists('TEXT', $v)) {
        return trim((string) $v['TEXT']);
    }
    if (is_array($v)) {
        return trim((string) ($v[0] ?? ''));
    }
    return trim((string) $v);
}

/**
 * Анонс карточки: превью элемента или первый абзац из детального HTML (как на uuopera.ru).
 * На оригинале — ровно 1 абзац (~150-250 символов).
 */
function uuopera_afisha_card_teaser_html(string $previewText, string $detailHtml, int $maxPlainChars = 300): string
{
    $previewText = trim($previewText);
    if ($previewText !== '') {
        return $previewText;
    }
    $detailHtml = trim($detailHtml);
    if ($detailHtml === '') {
        return '';
    }
    if (preg_match_all('/<p\b[^>]*>[\s\S]*?<\/p>/i', $detailHtml, $m)) {
        foreach ($m[0] as $block) {
            $plain = trim(strip_tags($block));
            if ($plain === '') {
                continue;
            }
            // Если абзац слишком длинный — обрезаем
            if (mb_strlen($plain) > $maxPlainChars) {
                $truncated = mb_substr($plain, 0, $maxPlainChars - 1) . '…';
                return '<p>' . htmlspecialchars($truncated, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
            }
            return $block;
        }
    }
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($detailHtml)));
    if ($plain === '') {
        return '';
    }
    if (mb_strlen($plain) > $maxPlainChars) {
        $plain = mb_substr($plain, 0, $maxPlainChars - 1) . '…';
    }

    return '<p>' . htmlspecialchars($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
}

function uuopera_afisha_hero_srcset_from_file_id(int $fileId): string
{
    if ($fileId <= 0) {
        return '';
    }
    $widths = [1920, 1200, 768, 480];
    $parts = [];
    foreach ($widths as $w) {
        $r = CFile::ResizeImageGet(
            $fileId,
            ['width' => $w, 'height' => 99999],
            BX_RESIZE_IMAGE_PROPORTIONAL,
            true
        );
        if (is_array($r) && !empty($r['src'])) {
            $parts[] = (string) $r['src'] . ' ' . $w . 'w';
        }
    }
    return implode(', ', $parts);
}

function uuopera_afisha_event_parse_sessions_json(string $json): array
{
    $json = trim($json);
    if ($json === '') {
        return [];
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }
    $out = [];
    foreach ($decoded as $row) {
        if (!is_array($row) || count($row) < 2) {
            continue;
        }
        $out[] = [(string) $row[0], (int) $row[1]];
    }
    return $out;
}

/**
 * @return array<string, mixed>
 */
function uuopera_afisha_event_get_data(string $code): array
{
    $data = uuopera_afisha_event_empty_data();
    $iblockId = uuopera_afisha_events_iblock_id();
    if ($iblockId <= 0 || $code === '' || !Loader::includeModule('iblock')) {
        return $data;
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
        ['ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_TEXT', 'DETAIL_TEXT_TYPE']
    );

    $ob = $res->GetNextElement();
    if (!$ob) {
        return $data;
    }

    $fields = $ob->GetFields();
    // GetProperties() fails in demo mode; use GetProperty()-based helper instead
    $elementId = (int) ($fields['ID'] ?? 0);
    $propCodes = [
        'CATEGORY', 'LAYOUT', 'AGE', 'SESSIONS_JSON', 'PARTICIPANTS_HTML',
        'CONTENT_HTML', 'RADARIO_AFISHA_KEY', 'RADARIO_HERO_EVENT_ID', 'RADARIO_HERO_MODE',
        'HERO_META_HTML', 'HERO_IMAGE', 'HERO_SRCSET', 'SLIDER_ID',
        'FOOTER_DURATION', 'FOOTER_PRICE', 'PUSHKIN_CARD', 'GALLERY',
    ];
    $props = uuopera_afisha_read_props($iblockId, $elementId, $propCodes);

    $str = static function (array $p): string {
        $v = $p['VALUE'] ?? '';
        return is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
    };

    require_once __DIR__ . '/uuopera_iblock_gallery.php';
    $gallery = uuopera_iblock_gallery_paths_from_file_property($props['GALLERY'] ?? null);

    $heroEventId = (int) $str($props['RADARIO_HERO_EVENT_ID'] ?? []);

    $sliderId = $str($props['SLIDER_ID'] ?? []);
    if ($sliderId === '') {
        $sliderId = 'slider';
    }

    $layout = strtolower($str($props['LAYOUT'] ?? []));
    if ($layout !== 'excursion') {
        $layout = 'event';
    }

    $sessions = uuopera_afisha_event_parse_sessions_json(uuopera_afisha_prop_value_plain($props['SESSIONS_JSON'] ?? []));

    $heroImage = '';
    $heroSrcset = '';
    $previewId = (int) ($fields['PREVIEW_PICTURE'] ?? 0);
    if ($previewId > 0) {
        $path = CFile::GetPath($previewId);
        if ($path !== false && $path !== '') {
            $heroImage = (string) $path;
        }
        $heroSrcset = uuopera_afisha_hero_srcset_from_file_id($previewId);
    }
    if ($heroImage === '') {
        $heroImage = $str($props['HERO_IMAGE'] ?? []);
        $heroSrcset = $str($props['HERO_SRCSET'] ?? []);
    }

    $pushkin = strtoupper($str($props['PUSHKIN_CARD'] ?? [])) === 'Y';

    $contentHtml = uuopera_afisha_prop_text_html($props['CONTENT_HTML'] ?? []);
    // When local gallery is present the slider lives in _afisha_slider_fragment.php;
    // strip duplicate lazyblock-image-slider-* divs from scraped content_html.
    if ($gallery !== []) {
        $contentHtml = trim(uuopera_afisha_strip_slider_blocks($contentHtml));
    }

    return [
        'name' => trim((string) ($fields['NAME'] ?? '')),
        'layout' => $layout,
        'category' => $str($props['CATEGORY'] ?? []),
        'age' => $str($props['AGE'] ?? []),
        'hero_image' => $heroImage,
        'hero_srcset' => $heroSrcset,
        'hero_meta_html' => uuopera_afisha_prop_text_html($props['HERO_META_HTML'] ?? []),
        'radario_hero_mode' => strtolower($str($props['RADARIO_HERO_MODE'] ?? [])),
        'radario_afisha_key' => $str($props['RADARIO_AFISHA_KEY'] ?? []),
        'radario_hero_event_id' => $heroEventId,
        'sessions' => $sessions,
        'participants_html' => uuopera_afisha_prop_text_html($props['PARTICIPANTS_HTML'] ?? []),
        'description_html' => (string) ($fields['DETAIL_TEXT'] ?? ''),
        'content_html' => $contentHtml,
        'footer_duration' => $str($props['FOOTER_DURATION'] ?? []),
        'footer_price' => $str($props['FOOTER_PRICE'] ?? []),
        'slider_id' => $sliderId,
        'gallery' => $gallery,
        'pushkin_card' => $pushkin,
    ];
}

function uuopera_afisha_format_sessions_line(array $sessions): string
{
    if ($sessions === []) {
        return '';
    }
    static $monthMap = [
        'января' => 1, 'февраля' => 2, 'марта' => 3, 'апреля' => 4,
        'мая' => 5, 'июня' => 6, 'июля' => 7, 'августа' => 8,
        'сентября' => 9, 'октября' => 10, 'ноября' => 11, 'декабря' => 12,
    ];
    static $monthNames = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    ];
    $now = time();
    $sep = str_repeat("\xc2\xa0", 4);
    $parts = [];
    foreach ($sessions as $pair) {
        if (!is_array($pair) || count($pair) < 2) {
            continue;
        }
        $label = trim((string) $pair[0]);
        // Формат: "9 апреля 14:00" или "31 января"
        if (preg_match('/^(\d{1,2})\s+(' . implode('|', array_keys($monthMap)) . ')(?:\s+(\d{2}:\d{2}))?/u', $label, $m)) {
            $day = (int) $m[1];
            $monthNum = $monthMap[$m[2]] ?? 0;
            $timeParts = isset($m[3]) ? explode(':', $m[3]) : ['0', '0'];
            $year = (int) date('Y');
            $ts = mktime((int) $timeParts[0], (int) ($timeParts[1] ?? 0), 0, $monthNum, $day, $year);
            if ($ts < $now) {
                $ts = mktime((int) $timeParts[0], (int) ($timeParts[1] ?? 0), 0, $monthNum, $day, $year + 1);
            }
            if ($ts < $now) {
                continue;
            }
            $parts[] = $day . "\xc2\xa0" . ($monthNames[$monthNum] ?? '');
        } else {
            $parts[] = $label;
        }
    }
    // Убираем дубликаты (могут быть показы в одно день в разное время)
    $parts = array_values(array_unique($parts));
    return implode($sep, $parts);
}

/**
 * Сегменты URL /afisha/{slug}/… — как в каталогах www/afisha и в uuopera_dispatch.
 *
 * @return list<string>
 */
function uuopera_afisha_known_url_slugs(): array
{
    return [
        'opera', 'ballet', 'concert', 'excursions', 'festivals',
        'online', 'performances', 'no-category', 'abonement', 'musical',
    ];
}

/**
 * Единый slug для ссылок в списках: свойство CATEGORY, SOURCE_URL импорта, раздел ИБ.
 *
 * @param array<string, array<string, mixed>> $props
 */
function uuopera_afisha_resolve_listing_category_slug(array $props, string $elementCode, int $sectionId, int $iblockId): string
{
    $str = static function (array $p): string {
        $v = $p['VALUE'] ?? '';
        return is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
    };

    $code = trim($elementCode);
    $src = $str($props['SOURCE_URL'] ?? []);
    if ($code !== '' && $src !== '' && preg_match('#/afisha/([a-z0-9_-]+)/' . preg_quote($code, '#') . '/?$#i', $src, $m)) {
        return strtolower($m[1]);
    }

    $propCat = $str($props['CATEGORY'] ?? []);
    $ascii = strtolower($propCat);
    if ($ascii !== '' && preg_match('/^[a-z0-9_-]+$/', $ascii)) {
        return $ascii;
    }

    $lower = mb_strtolower($propCat, 'UTF-8');
    $map = [
        'опера' => 'opera',
        'балет' => 'ballet',
        'концерт' => 'concert',
        'концерты' => 'concert',
        'экскурсии' => 'excursions',
        'экскурсия' => 'excursions',
        'фестивали' => 'festivals',
        'фестиваль' => 'festivals',
        'онлайн' => 'online',
        'спектакли' => 'performances',
        'спектакль' => 'performances',
        'представления' => 'performances',
        'без категории' => 'no-category',
        'абонемент' => 'abonement',
        'мюзикл' => 'musical',
        'музыкальный' => 'musical',
    ];
    if (isset($map[$lower])) {
        return $map[$lower];
    }

    if ($sectionId > 0 && $iblockId > 0) {
        $sec = CIBlockSection::GetList(
            [],
            ['ID' => $sectionId, 'IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['ID', 'CODE']
        )->Fetch();
        if (is_array($sec)) {
            $sc = strtolower(trim((string) ($sec['CODE'] ?? '')));
            if ($sc !== '' && preg_match('/^[a-z0-9_-]+$/', $sc)) {
                return $sc;
            }
        }
    }

    return 'no-category';
}

/**
 * Читает свойства элемента через CIBlockElement::GetProperty (GetNextElement->GetProperties не работает в демо).
 *
 * @return array<string, array{VALUE: mixed, PROPERTY_TYPE: string}>
 */
/**
 * Strips <div class="lazyblock-image-slider-*"> blocks from HTML, leaving surrounding content intact.
 * Used to avoid duplicate sliders when a local GALLERY property is present.
 */
function uuopera_afisha_strip_slider_blocks(string $html): string
{
    $result = '';
    $pos = 0;
    $len = strlen($html);
    while ($pos < $len) {
        $found = strpos($html, '<div class="lazyblock-image-slider-', $pos);
        if ($found === false) {
            $result .= substr($html, $pos);
            break;
        }
        $result .= substr($html, $pos, $found - $pos);
        // Find end of this div block via depth counting
        $depth = 1;
        $inner = $found + 4; // skip '<div'
        while ($depth > 0 && $inner < $len) {
            $nextOpen  = strpos($html, '<div', $inner);
            $nextClose = strpos($html, '</div>', $inner);
            if ($nextClose === false) {
                break;
            }
            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $inner = $nextOpen + 4;
            } else {
                $depth--;
                $inner = $nextClose + 6;
            }
        }
        $pos = $inner;
    }
    return $result;
}

function uuopera_afisha_read_props(int $iblockId, int $elementId, array $codes): array
{
    $result = [];
    foreach ($codes as $code) {
        $res = CIBlockElement::GetProperty($iblockId, $elementId, ['sort' => 'asc'], ['CODE' => $code]);
        $values = [];
        $propType = '';
        while ($p = $res->Fetch()) {
            $values[] = $p['VALUE'];
            if ($propType === '') {
                $propType = (string) ($p['PROPERTY_TYPE'] ?? '');
            }
        }
        if ($values !== []) {
            $result[$code] = ['VALUE' => count($values) === 1 ? $values[0] : $values, 'PROPERTY_TYPE' => $propType];
        }
    }
    return $result;
}

/**
 * @return list<array{
 *   code: string,
 *   category: string,
 *   name: string,
 *   url: string,
 *   hero_image: string,
 *   hero_srcset: string,
 *   sessions_line: string,
 *   teaser_html: string,
 *   radario_afisha_key: string,
 *   radario_event_id: int,
 *   age: string,
 *   pushkin_card: bool
 * }>
 */
function uuopera_afisha_list_events(string $categoryFilter, int $limit = 0, string $sortActiveFrom = 'DESC', string $minActiveFrom = ''): array
{
    $iblockId = uuopera_afisha_events_iblock_id();
    if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }

    $filter = [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'CHECK_PERMISSIONS' => 'Y',
    ];
    if ($minActiveFrom !== '') {
        $filter['>=ACTIVE_FROM'] = $minActiveFrom;
    }
    $cat = strtolower(trim($categoryFilter));

    $nav = false;
    if ($limit > 0) {
        $nav = ['nTopCount' => $limit];
    }

    $sortDir = strtoupper($sortActiveFrom) === 'ASC' ? 'ASC' : 'DESC';

    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ACTIVE_FROM' => $sortDir, 'ID' => 'DESC'],
        $filter,
        false,
        $nav,
        ['ID', 'NAME', 'CODE', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PREVIEW_PICTURE', 'IBLOCK_SECTION_ID']
    );

    $str = static function (array $p): string {
        $v = $p['VALUE'] ?? '';
        return is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
    };

    $propCodes = ['CATEGORY', 'SESSIONS_JSON', 'RADARIO_AFISHA_KEY', 'RADARIO_HERO_EVENT_ID', 'AGE', 'HERO_IMAGE', 'HERO_SRCSET', 'PUSHKIN_CARD', 'SOURCE_URL'];

    // First pass: collect all rows (avoids N×M per-element property queries)
    $allRows = [];
    while ($row = $res->Fetch()) {
        $elCode = trim((string) ($row['CODE'] ?? ''));
        if ($elCode !== '') {
            $allRows[(int) $row['ID']] = $row;
        }
    }

    if (empty($allRows)) {
        return [];
    }

    // Batch-load all properties for all elements in one call
    $rawAll = [];
    CIBlockElement::GetPropertyValuesArray(
        $rawAll,
        $iblockId,
        ['ID' => array_keys($allRows)],
        false
    );

    // Normalize to ['VALUE' => ...] format expected by $str() and callers
    $propsMap = [];
    foreach ($rawAll as $elId => $propsByCode) {
        foreach ($propsByCode as $pCode => $rawVal) {
            if (!is_array($rawVal) || array_key_exists('VALUE', $rawVal)) {
                $val = is_array($rawVal) ? ($rawVal['VALUE'] ?? '') : $rawVal;
            } else {
                $val = count($rawVal) === 1 ? ($rawVal[0] ?? '') : $rawVal;
            }
            $propsMap[(int) $elId][$pCode] = ['VALUE' => $val, 'PROPERTY_TYPE' => ''];
        }
    }

    $out = [];
    foreach ($allRows as $elementId => $row) {
        $props = $propsMap[$elementId] ?? [];
        $sectionId = (int) ($row['IBLOCK_SECTION_ID'] ?? 0);
        $code = (string) ($row['CODE'] ?? '');

        $slug = uuopera_afisha_resolve_listing_category_slug($props, $code, $sectionId, $iblockId);
        if ($cat !== '' && $cat !== 'all' && $slug !== $cat) {
            continue;
        }
        if ($cat === '' && $slug === 'news') {
            continue;
        }
        $sessions = uuopera_afisha_event_parse_sessions_json(uuopera_afisha_prop_value_plain($props['SESSIONS_JSON'] ?? []));
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
        if ($heroImage === '') {
            $heroImage = $str($props['HERO_IMAGE'] ?? []);
            $heroSrcset = $str($props['HERO_SRCSET'] ?? []);
        }
        $teaserHtml = uuopera_afisha_card_teaser_html(
            (string) ($row['PREVIEW_TEXT'] ?? ''),
            (string) ($row['DETAIL_TEXT'] ?? '')
        );

        $out[] = [
            'code' => $code,
            'category' => $slug,
            'name' => trim((string) ($row['NAME'] ?? '')),
            'url' => '/afisha/' . rawurlencode($slug) . '/' . rawurlencode($code) . '/',
            'hero_image' => $heroImage,
            'hero_srcset' => $heroSrcset,
            'sessions_line' => uuopera_afisha_format_sessions_line($sessions),
            'teaser_html' => $teaserHtml,
            'radario_afisha_key' => $str($props['RADARIO_AFISHA_KEY'] ?? []),
            'radario_event_id' => (int) $str($props['RADARIO_HERO_EVENT_ID'] ?? []),
            'age' => $str($props['AGE'] ?? []),
            'pushkin_card' => strtoupper($str($props['PUSHKIN_CARD'] ?? [])) === 'Y',
        ];
    }

    return $out;
}

/**
 * Главная как на uuopera.ru: сетка начинается с экскурсий, слайдер — из «обычных» событий
 * (на оригинале в слайдере нет экскурсии «Путешествие по театру», она первая в сетке).
 *
 * @return array{grid: list<array<string, mixed>>, slides: list<array{name: string, link_url: string, subtext_html: string, age_mark: string, radario_afisha_key: string, intickets_url: string, image: string, srcset: string}>}
 */
function uuopera_afisha_home_bundle(int $gridLimit = 8, int $sliderLimit = 4): array
{
    $gridLimit = max(1, min(40, $gridLimit));
    $sliderLimit = max(0, min(12, $sliderLimit));
    $pool = max($gridLimit + $sliderLimit + 10, 24);
    $today = date('d.m.Y H:i:s');
    $flat = uuopera_afisha_list_events('', $pool, 'ASC', $today);

    $exc = [];
    $rest = [];
    foreach ($flat as $row) {
        if (($row['category'] ?? '') === 'excursions') {
            $exc[] = $row;
        } else {
            $rest[] = $row;
        }
    }

    $ordered = array_merge($exc, $rest);
    $grid = array_slice($ordered, 0, $gridLimit);

    $slideRows = array_slice($rest, 0, $sliderLimit);
    $slides = [];
    foreach ($slideRows as $e) {
        $slides[] = [
            'name' => (string) ($e['name'] ?? ''),
            'link_url' => (string) ($e['url'] ?? '/afisha/'),
            'subtext_html' => (string) ($e['teaser_html'] ?? ''),
            'age_mark' => (string) ($e['age'] ?? ''),
            'radario_afisha_key' => (string) ($e['radario_afisha_key'] ?? ''),
            'intickets_url' => '',
            'image' => (string) ($e['hero_image'] ?? ''),
            'srcset' => (string) ($e['hero_srcset'] ?? ''),
        ];
    }

    return ['grid' => $grid, 'slides' => $slides];
}
