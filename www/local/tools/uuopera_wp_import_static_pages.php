<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_wp_import_static_pages.php [--dry-run]
 *
 * Imports specific WP pages into the Bitrix static pages iblock.
 * Decodes wp:lazyblock/text blocks and strips WP block comments from content.
 * Idempotent: re-runs update existing elements by XML_ID.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..') ?: '';
if ($_SERVER['DOCUMENT_ROOT'] === '') {
    fwrite(STDERR, "DOCUMENT_ROOT not found\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module not available\n");
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/init.php';

$dry = in_array('--dry-run', array_slice($argv, 1), true);

$staticIblockId = uuopera_cms_static_pages_iblock_id();
if ($staticIblockId <= 0) {
    fwrite(STDERR, "static pages iblock not configured — run uuopera_cms_iblocks_install.php\n");
    exit(1);
}

/** @var \Bitrix\Main\DB\Connection $bxConn */
$bxConn = \Bitrix\Main\Application::getConnection();
$db = $bxConn->getResource();
if (!$db instanceof mysqli) {
    fwrite(STDERR, "Cannot get mysqli resource\n");
    exit(1);
}

// Pages to import: [wp_page_id => request_path]
// Parent page for-visitors (31394) provides the intro text shown on all sub-pages
$pageMap = [
    31394 => '/for-visitors',
    31398 => '/for-visitors/etiquette',
    31405 => '/for-visitors/ticket-refund',
    31407 => '/for-visitors/discounted-tickets',
    31409 => '/for-visitors/dostupnaya-sreda',
    31191 => '/documents',
    31169 => '/brandbook',
    31012 => '/contacts-info',   // WP contacts page — stored for reference, not the main contacts
];

$ids = implode(', ', array_keys($pageMap));
$rows = $db->query(
    'SELECT ID, post_title, post_content FROM wp_posts WHERE ID IN (' . $ids . ')'
)->fetch_all(MYSQLI_ASSOC);

$wpPages = [];
foreach ($rows as $r) {
    $wpPages[(int) $r['ID']] = $r;
}

// --- WP block content decoder ---

/**
 * Decodes wp:lazyblock/text block: extracts the JSON "content" attribute and wraps in prod HTML.
 * <!-- wp:lazyblock/text {"content":"<html>","size":"text-medium","blockId":"abc"} /-->
 */
function uuopera_decode_lazyblock_text(string $raw): string
{
    // Match self-closing lazyblock/text blocks
    $pattern = '/<!--\s*wp:lazyblock\/text\s+(\{.*?\})\s*\/-->/s';
    $result = preg_replace_callback($pattern, static function (array $m): string {
        $json = json_decode($m[1], true);
        if (!is_array($json)) {
            return '';
        }
        $content = (string) ($json['content'] ?? '');
        if ($content === '') {
            return '';
        }
        $blockId = (string) ($json['blockId'] ?? 'block');
        $size    = (string) ($json['size'] ?? 'text-medium');
        // size may be "text-extra-large" etc — use as-is
        return '<div class="lazyblock-text-' . htmlspecialchars($blockId, ENT_QUOTES | ENT_HTML5) . ' wp-block-lazyblock-text">'
            . "\n\t" . '<div class="flex flex-col gap-[1em] ' . htmlspecialchars($size, ENT_QUOTES | ENT_HTML5) . '">'
            . "\n\t\t" . $content
            . "\n\t" . '</div>'
            . "\n" . '</div>';
    }, $raw);
    return is_string($result) ? $result : $raw;
}

/**
 * Strip remaining WP block comments <!-- wp:... --> and <!-- /wp:... -->
 * but keep inner HTML content.
 */
function uuopera_strip_wp_block_comments(string $html): string
{
    $html = preg_replace('/<!--\s*\/?wp:(?:(?!-->).)*?-->/s', '', $html) ?? $html;
    $html = preg_replace('/<!--more(?:(?!-->).)*?-->/s', '', $html) ?? $html;
    $html = preg_replace('/\n{3,}/', "\n\n", $html) ?? $html;
    return trim($html);
}

/**
 * Full WP content → clean HTML.
 */
function uuopera_wp_content_to_html(string $raw): string
{
    $html = uuopera_decode_lazyblock_text($raw);
    $html = uuopera_strip_wp_block_comments($html);
    return $html;
}

// --- Import pages ---

global $APPLICATION;
$counters = ['added' => 0, 'updated' => 0, 'errors' => 0];

foreach ($pageMap as $wpId => $requestPath) {
    $page = $wpPages[$wpId] ?? null;
    if ($page === null) {
        fwrite(STDERR, "WP page {$wpId} not found in DB\n");
        continue;
    }

    $title   = (string) ($page['post_title'] ?? '');
    $raw     = (string) ($page['post_content'] ?? '');
    $html    = uuopera_wp_content_to_html($raw);
    $xmlId   = 'uuopera_wp_page_' . $wpId;
    $normPath = uuopera_cms_normalize_request_path($requestPath);

    // Check existing by XML_ID
    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $staticIblockId, '=XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID', 'CODE']
    );
    $existing = $res ? $res->Fetch() : false;
    $existId  = is_array($existing) ? (int) ($existing['ID'] ?? 0) : 0;

    $code = 'pg-' . $wpId;
    if ($existId > 0 && !empty($existing['CODE'])) {
        $code = (string) $existing['CODE'];
    }

    $fields = [
        'MODIFIED_BY'       => 1,
        'IBLOCK_ID'         => $staticIblockId,
        'IBLOCK_SECTION_ID' => false,
        'XML_ID'            => $xmlId,
        'NAME'              => $title !== '' ? $title : $normPath,
        'CODE'              => $code,
        'ACTIVE'            => 'Y',
        'SORT'              => 500,
        'PREVIEW_TEXT'      => '',
        'PREVIEW_TEXT_TYPE' => 'text',
        'DETAIL_TEXT'       => $html,
        'DETAIL_TEXT_TYPE'  => 'html',
    ];

    $props = [
        'REQUEST_PATH'  => $normPath,
        'HEADER_SCHEMA' => 'beige',
    ];

    if ($dry) {
        $action = $existId > 0 ? 'update' : 'add';
        echo "[dry] {$action} wp#{$wpId} \"{$title}\" → {$normPath}\n";
        $existId > 0 ? $counters['updated']++ : $counters['added']++;
        continue;
    }

    $el = new CIBlockElement();
    if ($existId > 0) {
        $ok = $el->Update($existId, $fields);
        if (!$ok) {
            $err = is_object($APPLICATION) && $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : '?';
            fwrite(STDERR, "Update failed wp#{$wpId}: {$err}\n");
            $counters['errors']++;
            continue;
        }
        CIBlockElement::SetPropertyValuesEx($existId, $staticIblockId, $props);
        $counters['updated']++;
        echo "Updated wp#{$wpId} → {$normPath}\n";
    } else {
        $fields['PROPERTY_VALUES'] = $props;
        $newId = (int) $el->Add($fields);
        if ($newId <= 0) {
            $err = is_object($APPLICATION) && $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : '?';
            fwrite(STDERR, "Add failed wp#{$wpId}: {$err}\n");
            $counters['errors']++;
            continue;
        }
        CIBlockElement::SetPropertyValuesEx($newId, $staticIblockId, $props);
        $counters['added']++;
        echo "Added wp#{$wpId} → {$normPath}\n";
    }
}

echo ($dry ? '(dry-run) ' : '')
    . 'Added: ' . $counters['added']
    . ', Updated: ' . $counters['updated']
    . ', Errors: ' . $counters['errors'] . "\n";
exit($counters['errors'] > 0 ? 1 : 0);
