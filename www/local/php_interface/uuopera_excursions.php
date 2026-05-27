<?php

declare(strict_types=1);

/**
 * Инфоблок «Экскурсии» (uuopera / uuopera_excursions): контент только из админки.
 * Структура: uuopera_excursions_iblock_install.php · импорт с прода: uuopera_excursions_import_uuopera.php
 */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;

function uuopera_excursions_iblock_id(): int
{
    if (!class_exists(Option::class)) {
        return 0;
    }
    $raw = (string) Option::get('uuopera', 'excursions_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

/**
 * Пустой каркас, если элемент в инфоблоке не найден или не настроен.
 *
 * @return array{
 *   name: string,
 *   hero_image: string,
 *   hero_srcset: string,
 *   price_value: string,
 *   duration_hero: string,
 *   radario_afisha_key: string,
 *   sessions: list<array{0: string, 1: int}>,
 *   gallery: list<string>,
 *   slider_id: string,
 *   body_html: string,
 *   footer_duration: string,
 *   footer_price: string
 * }
 */
function uuopera_excursion_empty_data(): array
{
    return [
        'name' => '',
        'hero_image' => '',
        'hero_srcset' => '',
        'price_value' => '',
        'duration_hero' => '',
        'radario_afisha_key' => '',
        'sessions' => [],
        'gallery' => [],
        'slider_id' => 'slider',
        'body_html' => '',
        'footer_duration' => '',
        'footer_price' => '',
    ];
}

/** @deprecated Используйте uuopera_excursion_empty_data(); контент задаётся в инфоблоке или импортом. */
function uuopera_excursion_default_data(): array
{
    return uuopera_excursion_empty_data();
}

/** @param CMain $APPLICATION */
function uuopera_excursion_apply_title($APPLICATION): void
{
    $code = (string) ($GLOBALS['UUOPERA_EXCURSION_CODE'] ?? '');
    $suffix = ' - Бурятский театр оперы и балета';
    $fallback = 'Экскурсия' . $suffix;

    if ($code === '' || !Loader::includeModule('iblock')) {
        $APPLICATION->SetTitle($fallback);
        $APPLICATION->SetPageProperty('title', $fallback);
        return;
    }

    $iblockId = uuopera_excursions_iblock_id();
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
            $name = 'Экскурсия';
        }
        $full = $name . $suffix;
        $APPLICATION->SetTitle($full);
        $APPLICATION->SetPageProperty('title', $full);
        return;
    }

    $APPLICATION->SetTitle($fallback);
    $APPLICATION->SetPageProperty('title', $fallback);
}

function uuopera_excursion_hero_srcset_from_file_id(int $fileId): string
{
    if ($fileId <= 0) {
        return '';
    }
    $widths = [1920, 1200, 768, 480];
    $parts = [];
    $mode = defined('BX_RESIZE_IMAGE_PROPORTIONAL') ? BX_RESIZE_IMAGE_PROPORTIONAL : 2;
    foreach ($widths as $w) {
        $r = CFile::ResizeImageGet(
            $fileId,
            ['width' => $w, 'height' => 99999],
            $mode,
            true
        );
        if (is_array($r) && !empty($r['src'])) {
            $parts[] = (string) $r['src'] . ' ' . $w . 'w';
        }
    }
    return implode(', ', $parts);
}

function uuopera_excursion_parse_sessions_json(string $json): array
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
 * Данные страницы только из инфоблока (без подмешивания хардкода с продакшена).
 *
 * @return array<string, mixed>
 */
function uuopera_excursion_get_data(string $code): array
{
    $data = uuopera_excursion_empty_data();
    $iblockId = uuopera_excursions_iblock_id();
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
    $props = $ob->GetProperties();

    $str = static function (array $p): string {
        $v = $p['VALUE'] ?? '';
        return is_array($v) ? trim((string) ($v[0] ?? '')) : trim((string) $v);
    };

    $gallery = uuopera_iblock_gallery_paths_from_file_property($props['GALLERY'] ?? null);
    if ($gallery === []) {
        $galProp = $props['GALLERY_URL'] ?? [];
        if (!empty($galProp['VALUE'])) {
            $vals = $galProp['VALUE'];
            if (!is_array($vals)) {
                $vals = [$vals];
            }
            foreach ($vals as $u) {
                $u = trim((string) $u);
                if ($u !== '') {
                    $gallery[] = $u;
                }
            }
        }
    }

    $sessions = uuopera_excursion_parse_sessions_json($str($props['SESSIONS_JSON'] ?? []));

    $sliderId = $str($props['SLIDER_ID'] ?? []);
    if ($sliderId === '') {
        $sliderId = 'slider';
    }

    $heroImage = '';
    $heroSrcset = '';
    $previewId = (int) ($fields['PREVIEW_PICTURE'] ?? 0);
    if ($previewId > 0) {
        $path = CFile::GetPath($previewId);
        if ($path !== false && $path !== '') {
            $heroImage = (string) $path;
        }
        $heroSrcset = uuopera_excursion_hero_srcset_from_file_id($previewId);
    }
    if ($heroImage === '') {
        $heroImage = $str($props['HERO_IMAGE'] ?? []);
        $heroSrcset = $str($props['HERO_SRCSET'] ?? []);
    }

    return [
        'name' => trim((string) ($fields['NAME'] ?? '')),
        'hero_image' => $heroImage,
        'hero_srcset' => $heroSrcset,
        'price_value' => $str($props['PRICE_VALUE'] ?? []),
        'duration_hero' => $str($props['DURATION_HERO'] ?? []),
        'radario_afisha_key' => $str($props['RADARIO_AFISHA_KEY'] ?? []),
        'sessions' => $sessions,
        'gallery' => $gallery,
        'slider_id' => $sliderId,
        'body_html' => uuopera_html_decode_content((string) ($fields['DETAIL_TEXT'] ?? '')),
        'footer_duration' => $str($props['FOOTER_DURATION'] ?? []),
        'footer_price' => $str($props['FOOTER_PRICE'] ?? []),
    ];
}
