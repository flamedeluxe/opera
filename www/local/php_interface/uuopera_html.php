<?php

declare(strict_types=1);

function uuopera_html_decode_content(string $html): string
{
    if ($html === '') {
        return '';
    }
    $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (
        str_contains($decoded, '&lt;')
        || str_contains($decoded, '&gt;')
        || str_contains($decoded, '&quot;')
        || str_contains($decoded, '&#')
    ) {
        $again = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($again !== $decoded) {
            $decoded = $again;
        }
    }

    return $decoded;
}

function uuopera_html_convert_su_spoilers(string $html): string
{
    if ($html === '') {
        return '';
    }
    $html = str_replace(['[_su_spoiler', '[_/su_spoiler]'], ['[su_spoiler', '[/su_spoiler]'], $html);
    $html = preg_replace('/<p>\s*(\[su_spoiler[^\]]*\])\s*<\/p>/iu', '$1', $html) ?? $html;
    $html = preg_replace('/<p>\s*(\[\/su_spoiler\])\s*<\/p>/iu', '$1', $html) ?? $html;

    $openTag = '[su_spoiler';
    while (($pos = stripos($html, $openTag)) !== false) {
        if (!preg_match(
            '/\[su_spoiler\s+title="([^"]*)"\s*\]/iu',
            $html,
            $titleMatch,
            0,
            $pos
        )) {
            break;
        }
        $title = htmlspecialchars($titleMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $openEnd = $pos + strlen($titleMatch[0]);
        $depth = 1;
        $cursor = $openEnd;
        $len = strlen($html);
        $closeEnd = null;
        while ($cursor < $len) {
            $nextOpen = stripos($html, $openTag, $cursor);
            $nextClose = stripos($html, '[/su_spoiler]', $cursor);
            if ($nextClose === false) {
                break;
            }
            if ($nextOpen !== false && $nextOpen < $nextClose) {
                $depth++;
                $cursor = $nextOpen + strlen($openTag);
                continue;
            }
            $depth--;
            $cursor = $nextClose + strlen('[/su_spoiler]');
            if ($depth === 0) {
                $closeEnd = $cursor;
                break;
            }
        }
        if ($closeEnd === null) {
            break;
        }
        $inner = substr($html, $openEnd, $closeEnd - $openEnd - strlen('[/su_spoiler]'));
        $replacement = '<div class="su-spoiler su-spoiler-style-default su-spoiler-icon-plus su-spoiler-closed" data-scroll-offset="0" data-anchor-in-url="no">'
            . '<div class="su-spoiler-title" tabindex="0" role="button"><span class="su-spoiler-icon"></span>' . $title . '</div>'
            . '<div class="su-spoiler-content su-u-clearfix su-u-trim">'
            . uuopera_html_convert_su_spoilers($inner)
            . '</div></div>';
        $html = substr($html, 0, $pos) . $replacement . substr($html, $closeEnd);
    }

    return $html;
}

function uuopera_html_prepare_documents_html(string $html): string
{
    if ($html === '') {
        return '';
    }
    if (str_contains($html, '[su_spoiler') || str_contains($html, '[_su_spoiler')) {
        $html = uuopera_html_convert_su_spoilers($html);
    }
    if (!str_contains($html, 'class="docs"') && str_contains($html, 'su-spoiler')) {
        $html = '<div class="docs">' . $html . '</div>';
    }
    if (!preg_match('/<h1\b/i', $html)) {
        $html = '<h1 class="text-h1">Документы</h1>' . "\n" . $html;
    }

    return $html;
}
