<?php

declare(strict_types=1);

/**
 * Парсинг HTML карточек афиши с uuopera.ru → единая структура для инфоблока.
 */

require_once __DIR__ . '/uuopera_excursion_parse_uuopera.php';

/**
 * @return array{
 *   layout: string,
 *   category: string,
 *   name: string,
 *   age: string,
 *   hero_image: string,
 *   hero_srcset: string,
 *   hero_meta_html: string,
 *   radario_hero_mode: string,
 *   radario_afisha_key: string,
 *   radario_hero_event_id: int,
 *   sessions: list<array{0: string, 1: int}>,
 *   participants_html: string,
 *   description_html: string,
 *   footer_duration: string,
 *   footer_price: string,
 *   slider_id: string,
 *   gallery: list<string>
 * }
 */
function uuopera_afisha_parse_empty_payload(): array
{
    return [
        'layout' => 'event',
        'category' => '',
        'name' => '',
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
        'footer_duration' => '',
        'footer_price' => '',
        'slider_id' => '',
        'gallery' => [],
    ];
}

/**
 * @param array<string, mixed> $ex
 * @return array<string, mixed>
 */
function uuopera_afisha_map_excursion_parse(array $ex, string $category): array
{
    $p = uuopera_afisha_parse_empty_payload();
    $p['layout'] = 'excursion';
    $p['category'] = $category !== '' ? $category : 'excursions';
    $p['name'] = (string) ($ex['name'] ?? '');
    $p['hero_image'] = (string) ($ex['hero_image'] ?? '');
    $p['hero_srcset'] = (string) ($ex['hero_srcset'] ?? '');
    $pv = (string) ($ex['price_value'] ?? '');
    $dh = (string) ($ex['duration_hero'] ?? '');
    if ($pv !== '' || $dh !== '') {
        $p['hero_meta_html'] = '';
        if ($pv !== '') {
            $p['hero_meta_html'] .= '<p>Цена билета: ' . htmlspecialchars($pv, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        }
        if ($dh !== '') {
            $p['hero_meta_html'] .= '<p>Продолжительность: ' . htmlspecialchars($dh, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
        }
    }
    $p['radario_hero_mode'] = 'afisha';
    $p['radario_afisha_key'] = (string) ($ex['radario_afisha_key'] ?? '');
    $p['radario_hero_event_id'] = 0;
    $p['sessions'] = is_array($ex['sessions'] ?? null) ? $ex['sessions'] : [];
    $p['participants_html'] = '';
    $p['description_html'] = (string) ($ex['body_html'] ?? '');
    $p['footer_duration'] = (string) ($ex['footer_duration'] ?? '');
    $p['footer_price'] = (string) ($ex['footer_price'] ?? '');
    $p['slider_id'] = (string) ($ex['slider_id'] ?? '');
    $p['gallery'] = is_array($ex['gallery'] ?? null) ? $ex['gallery'] : [];
    return $p;
}

/**
 * @return array<string, mixed>
 */
function uuopera_afisha_parse_uuopera_page(string $html, string $category = ''): array
{
    $out = uuopera_afisha_parse_empty_payload();
    $out['category'] = $category;

    $mainStart = stripos($html, '<main');
    $mainEnd = strripos($html, '</main>');
    if ($mainStart === false || $mainEnd === false || $mainEnd <= $mainStart) {
        return $out;
    }
    $main = substr($html, $mainStart, $mainEnd - $mainStart);

    if (preg_match('/<p>Цена билета:/u', $main) && preg_match('/<p>Продолжительность:\s*/u', $main)) {
        $ex = uuopera_excursion_parse_html_from_uuopera($html);
        return uuopera_afisha_map_excursion_parse($ex, $category);
    }

    if (preg_match('/<h1 class="text-h1">([\s\S]*?)<\/h1>/u', $main, $h1)) {
        $out['name'] = trim(html_entity_decode(strip_tags($h1[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    if (preg_match('/<h1 class="text-h1">[\s\S]*?<\/h1>\s*<\/div>\s*<div class="text-h2 pt-1">([^<]+)<\/div>/u', $main, $ag)) {
        $out['age'] = trim(html_entity_decode($ag[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    if (preg_match(
        '/data-header-color-schema="transparent"[\s\S]*?<img[^>]+src="([^"]+)"[^>]+class="[^"]*wp-post-image[^"]*"[^>]*srcset="([^"]*)"/u',
        $main,
        $img
    )) {
        $out['hero_image'] = trim($img[1]);
        $out['hero_srcset'] = trim($img[2]);
    } elseif (preg_match(
        '/<img[^>]+src="([^"]+)"[^>]+class="[^"]*wp-post-image[^"]*"[^>]+srcset="([^"]+)"/u',
        $main,
        $img2
    )) {
        $out['hero_image'] = trim($img2[1]);
        $out['hero_srcset'] = trim($img2[2]);
    }

    if (preg_match(
        '/<div class="flex flex-col sm:flex-row gap-5 sm:justify-between sm:items-end text-h2">\s*<div class="max-w-\[400px\]">([\s\S]*?)<\/div>/u',
        $main,
        $hm
    )) {
        $out['hero_meta_html'] = trim($hm[1]);
    }

    if (preg_match(
        '/<!--\s*Кнопка Купить билет в карточке события\. Радарио\. Начало\s*-->([\s\S]*?)<!--\s*Кнопка Купить билет в карточке события\. Радарио\. Начало Конец\s*-->/u',
        $main,
        $cta
    )) {
        $chunk = $cta[1];
        if (preg_match('/radario\.Widgets\.Afisha[\s\S]*?"key"\s*:\s*"\s*([^"]+?)\s*"/u', $chunk, $k)) {
            $out['radario_hero_mode'] = 'afisha';
            $out['radario_afisha_key'] = trim($k[1]);
            $out['radario_hero_event_id'] = 0;
        } elseif (preg_match('/radario\.Widgets\.Event[\s\S]*?"eventId"\s*:\s*"?(\d+)"?/u', $chunk, $e)) {
            $out['radario_hero_mode'] = 'event';
            $out['radario_afisha_key'] = '';
            $out['radario_hero_event_id'] = (int) $e[1];
        }
    }

    if (preg_match_all(
        '/white-space:\s*nowrap[^>]*>\s*([^<]+?)\s*<\/span>[\s\S]*?radario\.Widgets\.Event\s*\([\s\S]*?"eventId"\s*:\s*"?(\d+)"?/u',
        $main,
        $sess,
        PREG_SET_ORDER
    )) {
        foreach ($sess as $row) {
            $label = trim(html_entity_decode($row[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $eid = (int) $row[2];
            if ($label !== '' && $eid > 0) {
                $out['sessions'][] = [$label, $eid];
            }
        }
    }

    if (str_contains($main, 'Состав')) {
        $pStart = strpos($main, '<div class="flex flex-col gap-6 md:col-span-6 xl:col-span-4">');
        $pEndMarker = '<div class="flex flex-col gap-8 justify-between md:grid md:grid-cols-7 lg:grid-cols-6 xl:grid-cols-5 md:col-span-7 lg:col-span-6 lg:gap-x-5 xl:col-span-5 lg:col-start-7 xl:col-start-7';
        if ($pStart !== false) {
            $pEnd = strpos($main, $pEndMarker, $pStart);
            if ($pEnd !== false && $pEnd > $pStart) {
                $out['participants_html'] = trim(substr($main, $pStart, $pEnd - $pStart));
            }
        }
    }

    if (preg_match(
        '/<div class="md:col-span-6 lg:col-span-5 xl:col-span-3 flex flex-col gap-\[1em\]">([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>\s*<div class="flex flex-col gap-12 md:gap-15/u',
        $main,
        $textBlock
    )) {
        $out['description_html'] = trim($textBlock[1]);
    }

    if (preg_match('/<div class="swiper slider-default" data-slider-default="([^"]+)"/u', $main, $sl)) {
        $out['slider_id'] = trim($sl[1]);
    }

    if (preg_match('/<div class="swiper-wrapper">([\s\S]*?)<\/div>\s*<\/div>\s*<div class="mt-3/u', $main, $wrap)) {
        if (preg_match_all('/<img[^>]+src="(https:\/\/uuopera\.ru\/wp-content\/[^"]+)"/u', $wrap[1], $g)) {
            foreach ($g[1] as $u) {
                $u = trim($u);
                if ($u !== '') {
                    $out['gallery'][] = $u;
                }
            }
        }
    }

    $out['layout'] = 'event';
    return $out;
}
