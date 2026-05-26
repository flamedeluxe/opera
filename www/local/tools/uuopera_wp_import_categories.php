<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_wp_import_categories.php [--dry-run] [--with-thumb] [--category=slug,...]
 *
 * Imports WordPress posts from specific categories into the Bitrix news iblock
 * and assigns them to matching Bitrix sections (matched by slug = section CODE).
 *
 * Requires wp_* tables to already exist in the same MariaDB database as Bitrix.
 *
 * Idempotent: re-running updates existing elements by XML_ID = 'uuopera_wp_post_{wp_id}'.
 *
 * --with-thumb  Set PREVIEW_PICTURE from the local wp-content/uploads copy.
 *               Skips elements that already have a picture unless they are new.
 *
 * Default: imports all WP categories that have a matching Bitrix section CODE.
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

// --- Parse CLI args ---
$dry        = false;
$withThumb  = false;
$filterCategories = [];
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dry = true;
    } elseif ($arg === '--with-thumb') {
        $withThumb = true;
    } elseif (str_starts_with($arg, '--category=')) {
        foreach (explode(',', substr($arg, 11)) as $cat) {
            $cat = trim($cat);
            if ($cat !== '') {
                $filterCategories[] = $cat;
            }
        }
    } else {
        fwrite(STDERR, "Unknown arg: {$arg}\n");
        exit(1);
    }
}

// --- Bitrix setup ---
$newsIblockId = uuopera_news_iblock_id();
if ($newsIblockId <= 0) {
    fwrite(STDERR, "news iblock not configured — run uuopera_cms_iblocks_install.php first\n");
    exit(1);
}

/** @var \Bitrix\Main\DB\Connection $bxConn */
$bxConn = \Bitrix\Main\Application::getConnection();

// --- Load Bitrix news sections (CODE => {id, name}) ---
$secRows = $bxConn->query(
    'SELECT ID, CODE, NAME FROM b_iblock_section'
    . ' WHERE IBLOCK_ID = ' . $newsIblockId . " AND ACTIVE = 'Y'"
)->fetchAll();

/** @var array<string, array{id: int, name: string}> $sectionMap */
$sectionMap = [];
foreach ($secRows as $r) {
    $code = trim((string) ($r['CODE'] ?? ''));
    if ($code !== '') {
        $sectionMap[$code] = ['id' => (int) $r['ID'], 'name' => (string) $r['NAME']];
    }
}

if (empty($sectionMap)) {
    fwrite(STDERR, "No active sections in news iblock\n");
    exit(1);
}

if (empty($filterCategories)) {
    $filterCategories = array_keys($sectionMap);
}

$validCategories   = array_values(array_filter($filterCategories, fn($c) => isset($sectionMap[$c])));
$unknownCategories = array_values(array_diff($filterCategories, $validCategories));
if (!empty($unknownCategories)) {
    fwrite(STDERR, 'Warning: no Bitrix section for: ' . implode(', ', $unknownCategories) . "\n");
}
if (empty($validCategories)) {
    fwrite(STDERR, "No valid categories to import\n");
    exit(1);
}

echo 'Importing WP categories: ' . implode(', ', $validCategories) . "\n";

$db = $bxConn->getResource();
if (!$db instanceof mysqli) {
    fwrite(STDERR, "Cannot get mysqli resource\n");
    exit(1);
}

// --- Fetch WP posts for the given categories ---
$slugList = implode(', ', array_map(fn($s) => "'" . $db->real_escape_string($s) . "'", $validCategories));

$st = $db->prepare(
    'SELECT p.ID, p.post_date, p.post_title, p.post_content, p.post_excerpt,
            p.post_name, t.slug AS cat_slug
     FROM wp_posts p
     JOIN wp_term_relationships tr ON tr.object_id = p.ID
     JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
     JOIN wp_terms t ON t.term_id = tt.term_id
     WHERE tt.taxonomy = \'category\'
       AND t.slug IN (' . $slugList . ')
       AND p.post_type = \'post\'
       AND p.post_status = \'publish\'
     ORDER BY p.ID ASC, t.slug ASC'
);
if (!$st || !$st->execute()) {
    fwrite(STDERR, "WP posts query failed: " . $db->error . "\n");
    exit(1);
}

/** @var array<int, array{date:string,title:string,content:string,excerpt:string,name:string,slugs:list<string>}> $posts */
$posts = [];
foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
    $id = (int) $r['ID'];
    if (!isset($posts[$id])) {
        $posts[$id] = [
            'date'    => (string) $r['post_date'],
            'title'   => (string) $r['post_title'],
            'content' => (string) $r['post_content'],
            'excerpt' => (string) $r['post_excerpt'],
            'name'    => (string) $r['post_name'],
            'slugs'   => [],
        ];
    }
    $posts[$id]['slugs'][] = (string) $r['cat_slug'];
}
$st->close();

echo 'WP posts to import: ' . count($posts) . "\n";
if (empty($posts)) {
    echo "Nothing to import.\n";
    exit(0);
}

// --- Preload WP thumbnail local paths (wp_postmeta + wp_posts attachment GUID) ---
// GUIDs look like https://uuopera.ru/wp-content/uploads/YYYY/MM/file.jpg
// Local copy: {DOCUMENT_ROOT}/wp-content/uploads/YYYY/MM/file.jpg

/** @var array<int, string> $thumbPaths  wp_post_id => absolute local path */
$thumbPaths = [];

if ($withThumb && !empty($posts)) {
    $idList = implode(', ', array_keys($posts));
    $stThumb = $db->query(
        'SELECT pm.post_id, att.guid
         FROM wp_postmeta pm
         JOIN wp_posts att ON att.ID = CAST(pm.meta_value AS UNSIGNED) AND att.post_type = \'attachment\'
         WHERE pm.meta_key = \'_thumbnail_id\'
           AND pm.post_id IN (' . $idList . ')'
    );
    if ($stThumb) {
        while ($tr = $stThumb->fetch_assoc()) {
            $wpId = (int) $tr['post_id'];
            $guid = trim((string) ($tr['guid'] ?? ''));
            if ($guid === '') {
                continue;
            }
            // Strip any production origin and keep the /wp-content/uploads/... path
            $urlPath = parse_url($guid, PHP_URL_PATH);
            if (!is_string($urlPath) || !str_starts_with($urlPath, '/wp-content/')) {
                continue;
            }
            $localPath = $_SERVER['DOCUMENT_ROOT'] . $urlPath;
            if (is_file($localPath)) {
                $thumbPaths[$wpId] = $localPath;
            }
        }
    }
    echo 'Thumbnails found locally: ' . count($thumbPaths) . ' / ' . count($posts) . "\n";
}

// --- Helpers ---

function uuopera_wpcat_decode_lazyblock_text(string $raw): string
{
    $result = preg_replace_callback(
        '/<!--\s*wp:lazyblock\/text\s+(\{.*?\})\s*\/-->/s',
        static function (array $m): string {
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
            return '<div class="lazyblock-text-' . htmlspecialchars($blockId, ENT_QUOTES | ENT_HTML5) . ' wp-block-lazyblock-text">'
                . "\n\t" . '<div class="flex flex-col gap-[1em] ' . htmlspecialchars($size, ENT_QUOTES | ENT_HTML5) . '">'
                . "\n\t\t" . $content
                . "\n\t" . '</div>'
                . "\n" . '</div>';
        },
        $raw
    );
    return is_string($result) ? $result : $raw;
}

function uuopera_wpcat_strip_wp_block_comments(string $html): string
{
    // Match any HTML comment that starts with wp: (opening, closing, or self-closing)
    $html = preg_replace('/<!--\s*\/?wp:(?:(?!-->).)*?-->/s', '', $html) ?? $html;
    // Strip <!--more--> and <!--more ...--> variants
    $html = preg_replace('/<!--more(?:(?!-->).)*?-->/s', '', $html) ?? $html;
    $html = preg_replace('/\n{3,}/', "\n\n", $html) ?? $html;
    return trim($html);
}

function uuopera_wpcat_content_to_html(string $raw): string
{
    $html = uuopera_wpcat_decode_lazyblock_text($raw);
    $html = uuopera_wpcat_strip_wp_block_comments($html);
    // Rewrite production upload URLs to local relative paths
    $html = str_replace('https://uuopera.ru/wp-content/uploads/', '/wp-content/uploads/', $html);
    return $html;
}

function uuopera_wpcat_active_from(string $mysqlDate): string
{
    $t = strtotime($mysqlDate);
    if ($t === false || $t <= 0) {
        return '';
    }
    return function_exists('ConvertTimeStamp') ? (string) ConvertTimeStamp($t, 'FULL') : date('d.m.Y H:i:s', $t);
}

function uuopera_wpcat_code(string $slug, string $title, int $wpId): string
{
    $slug = trim($slug);
    if ($slug !== '' && preg_match('/^[a-z0-9_-]+$/i', $slug)) {
        return strtolower($slug);
    }
    if ($title !== '' && class_exists('CUtil')) {
        $t = (string) \CUtil::translit($title, 'ru', [
            'max_len' => 100, 'change_case' => 'L', 'replace_space' => '-', 'replace_other' => '-',
        ]);
        $t = trim($t, '-_');
        if ($t !== '') {
            return $t;
        }
    }
    return 'wp-post-' . $wpId;
}

global $APPLICATION;

$counters = ['added' => 0, 'updated' => 0, 'thumb_set' => 0, 'errors' => 0];

foreach ($posts as $wpId => $post) {
    $xmlId = 'uuopera_wp_post_' . $wpId;
    $code  = uuopera_wpcat_code($post['name'], $post['title'], $wpId);

    $primarySlug  = $post['slugs'][0];
    $primarySecId = $sectionMap[$primarySlug]['id'] ?? 0;

    // Find existing element: first by XML_ID, then by CODE (catches old-data duplicates)
    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $newsIblockId, '=XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID', 'CODE', 'PREVIEW_PICTURE']
    );
    $existing = $res ? $res->Fetch() : false;
    $existId  = is_array($existing) ? (int) ($existing['ID'] ?? 0) : 0;

    // Also purge any other elements with the same CODE that aren't the canonical one
    $res2 = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => $newsIblockId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'CODE', 'XML_ID', 'PREVIEW_PICTURE']
    );
    $allByCode = [];
    while ($r2 = $res2->Fetch()) {
        $allByCode[] = $r2;
    }

    if ($existId <= 0 && !empty($allByCode)) {
        // No element found by XML_ID — take the first by CODE as canonical
        $existing = $allByCode[0];
        $existId  = (int) $existing['ID'];
    }

    // Remove duplicates by CODE (keep the canonical existId)
    foreach ($allByCode as $dup) {
        $dupId = (int) ($dup['ID'] ?? 0);
        if ($dupId > 0 && $dupId !== $existId) {
            if (!$dry) {
                CIBlockElement::Delete($dupId);
            }
            fwrite(STDERR, "Removed duplicate element ID {$dupId} (same CODE {$code})\n");
        }
    }

    $hasPic   = $existId > 0 && !empty($existing['PREVIEW_PICTURE']);
    $elCode = ($existId > 0 && !empty($existing['CODE'])) ? (string) $existing['CODE'] : $code;

    // Build picture field (skip formats PHP/GD cannot handle, e.g. HEIC)
    $picField = false;
    if ($withThumb && !$hasPic && isset($thumbPaths[$wpId])) {
        $ext = strtolower(pathinfo($thumbPaths[$wpId], PATHINFO_EXTENSION));
        $supported = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (in_array($ext, $supported, true)) {
            $fa = \CFile::MakeFileArray($thumbPaths[$wpId]);
            if (is_array($fa) && empty($fa['error']) && !empty($fa['tmp_name'])) {
                $fa['MODULE_ID'] = 'iblock';
                $picField = $fa;
            }
        }
    }

    $fields = [
        'MODIFIED_BY'       => 1,
        'IBLOCK_ID'         => $newsIblockId,
        'IBLOCK_SECTION_ID' => $primarySecId > 0 ? $primarySecId : false,
        'XML_ID'            => $xmlId,
        'NAME'              => $post['title'] !== '' ? $post['title'] : $elCode,
        'CODE'              => $elCode,
        'ACTIVE'            => 'Y',
        'SORT'              => 500,
        'DATE_ACTIVE_FROM'  => uuopera_wpcat_active_from($post['date']),
        'PREVIEW_TEXT'      => uuopera_wpcat_strip_wp_block_comments($post['excerpt']),
        'PREVIEW_TEXT_TYPE' => 'html',
        'DETAIL_TEXT'       => uuopera_wpcat_content_to_html($post['content']),
        'DETAIL_TEXT_TYPE'  => 'html',
    ];
    if ($picField !== false) {
        $fields['PREVIEW_PICTURE'] = $picField;
        $fields['DETAIL_PICTURE']  = $picField;
    }

    if ($dry) {
        $action  = $existId > 0 ? 'update' : 'add';
        $thumbNote = $withThumb ? (isset($thumbPaths[$wpId]) ? ($hasPic ? ' [pic exists]' : ' [+thumb]') : ' [no local thumb]') : '';
        echo "[dry] {$action} wp#{$wpId} \"{$post['title']}\"{$thumbNote}\n";
        $existId > 0 ? $counters['updated']++ : $counters['added']++;
        if ($picField !== false) {
            $counters['thumb_set']++;
        }
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
        $elId = $existId;
        $counters['updated']++;
    } else {
        $elId = (int) $el->Add($fields);
        if ($elId <= 0) {
            $err = is_object($APPLICATION) && $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : '?';
            fwrite(STDERR, "Add failed wp#{$wpId}: {$err}\n");
            $counters['errors']++;
            continue;
        }
        $counters['added']++;
    }
    if ($picField !== false) {
        $counters['thumb_set']++;
    }

    // Assign sections in b_iblock_section_element
    $bxConn->query('DELETE FROM b_iblock_section_element WHERE IBLOCK_ELEMENT_ID = ' . $elId);
    foreach ($post['slugs'] as $slug) {
        $secId = $sectionMap[$slug]['id'] ?? 0;
        if ($secId > 0) {
            $bxConn->query(
                'INSERT IGNORE INTO b_iblock_section_element'
                . ' (IBLOCK_SECTION_ID, IBLOCK_ELEMENT_ID, ADDITIONAL_PROPERTY_ID)'
                . ' VALUES (' . $secId . ', ' . $elId . ', NULL)'
            );
        }
    }
}

echo ($dry ? '(dry-run) ' : '')
    . 'Added: ' . $counters['added']
    . ', Updated: ' . $counters['updated']
    . ($withThumb ? ', Thumbs set: ' . $counters['thumb_set'] : '')
    . ', Errors: ' . $counters['errors'] . "\n";
exit($counters['errors'] > 0 ? 1 : 0);
