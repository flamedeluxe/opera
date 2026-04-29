<?php

declare(strict_types=1);

/**
 * Разбор HTML страницы экскурсии с uuopera.ru (WordPress) → массив для инфоблока.
 * Без зависимостей от Битрикс.
 */

function uuopera_excursion_fetch_remote_html(string $url, int $timeoutSec = 45): string
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        throw new InvalidArgumentException('Некорректный URL.');
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('curl_init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (compatible; UuoperaImport/1.0)',
                'Accept: text/html,application/xhtml+xml',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            throw new RuntimeException('HTTP ошибка при загрузке: ' . $code);
        }
        return (string) $body;
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => $timeoutSec,
            'header' => "User-Agent: Mozilla/5.0 (compatible; UuoperaImport/1.0)\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        throw new RuntimeException('Не удалось скачать страницу (file_get_contents).');
    }
    return (string) $body;
}

/**
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
function uuopera_excursion_parse_html_from_uuopera(string $html): array
{
    $out = [
        'name' => '',
        'hero_image' => '',
        'hero_srcset' => '',
        'price_value' => '',
        'duration_hero' => '',
        'radario_afisha_key' => '',
        'sessions' => [],
        'gallery' => [],
        'slider_id' => '',
        'body_html' => '',
        'footer_duration' => '',
        'footer_price' => '',
    ];

    if (preg_match('/<h1 class="text-h1">([\s\S]*?)<\/h1>/u', $html, $h1)) {
        $out['name'] = trim(html_entity_decode(strip_tags($h1[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    if (preg_match(
        '/data-header-color-schema="transparent"[\s\S]*?<img[^>]+src="([^"]+)"[^>]+class="[^"]*wp-post-image[^"]*"[^>]*(?:srcset="([^"]*)")?/u',
        $html,
        $img
    )) {
        $out['hero_image'] = trim($img[1]);
        $out['hero_srcset'] = isset($img[2]) ? trim($img[2]) : '';
    }

    if ($out['hero_srcset'] === '' && preg_match(
        '/class="[^"]*wp-post-image[^"]*"[^>]+srcset="([^"]+)"/u',
        $html,
        $ss
    )) {
        $out['hero_srcset'] = trim($ss[1]);
    }

    if (preg_match(
        '/<div class="max-w-\[400px\]">\s*<p>Цена билета:\s*([^<]*)<\/p>\s*<p>Продолжительность:\s*([^<]*)<\/p>/u',
        $html,
        $price
    )) {
        $out['price_value'] = trim(html_entity_decode($price[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $out['duration_hero'] = trim(html_entity_decode($price[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    if (preg_match(
        '/radario\.Widgets\.Afisha\s*\(\s*\{[\s\S]*?"buttonPadding"\s*:\s*"8px 2px"[\s\S]*?"key"\s*:\s*"\s*([^"]+?)\s*"/u',
        $html,
        $af
    )) {
        $out['radario_afisha_key'] = trim($af[1]);
    }

    if (preg_match_all(
        '/white-space:\s*nowrap[^>]*>\s*([^<]+?)\s*<\/span>[\s\S]*?radario\.Widgets\.Event\s*\([\s\S]*?"eventId"\s*:\s*(\d+)/u',
        $html,
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

    if (preg_match(
        '/<div class="md:col-span-6 lg:col-span-5 xl:col-span-3 flex flex-col gap-\[1em\]">([\s\S]*?)<\/div>\s*<\/div>\s*<\/div>\s*<div class="flex flex-col gap-12 md:gap-15/u',
        $html,
        $textBlock
    )) {
        $inner = $textBlock[1];
        if (preg_match_all('/<p class="ds-markdown-paragraph">[\s\S]*?<\/p>/u', $inner, $mds)) {
            $out['body_html'] = implode("\n", $mds[0]);
        }
        if (preg_match('/<p>(Длительность:[^<]+)<\/p>\s*<p>(Цена билета:[^<]+)<\/p>/u', $inner, $ft)) {
            $out['footer_duration'] = trim(html_entity_decode($ft[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $out['footer_price'] = trim(html_entity_decode($ft[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
    }

    if (preg_match('/<div class="swiper slider-default" data-slider-default="([^"]+)"/u', $html, $sl)) {
        $out['slider_id'] = trim($sl[1]);
    }

    if (preg_match('/<main\b[^>]*>([\s\S]*)/iu', $html, $main)) {
        $mainHtml = $main[1];
        if (preg_match('/<div class="swiper-wrapper">([\s\S]*?)<\/div>\s*<\/div>\s*<div class="mt-3/u', $mainHtml, $wrap)) {
            if (preg_match_all('/<img[^>]+src="(https:\/\/uuopera\.ru\/wp-content\/[^"]+)"/u', $wrap[1], $g)) {
                foreach ($g[1] as $u) {
                    $u = trim($u);
                    if ($u !== '') {
                        $out['gallery'][] = $u;
                    }
                }
            }
        }
    }

    return $out;
}
