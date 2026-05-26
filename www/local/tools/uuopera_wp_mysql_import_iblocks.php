<?php

declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_wp_mysql_import_iblocks.php [--dry-run] [--only=pages,posts,persone,projects] [--with-thumb]
 * WordPress DB in a separate schema (see CLAUDE.md / .env.example).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..') ?: '';
if ($_SERVER['DOCUMENT_ROOT'] === '') {
    fwrite(STDERR, "DOC_ROOT missing\n");
    exit(1);
}
$DOCUMENT_ROOT = $_SERVER['DOCUMENT_ROOT'];

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $DOCUMENT_ROOT . '/bitrix/modules/main/include/prolog_before.php';

if (!extension_loaded('mysqli')) {
    fwrite(STDERR, "mysqli extension missing\n");
    exit(1);
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module missing\n");
    exit(1);
}

require_once $DOCUMENT_ROOT . '/local/php_interface/init.php';

function uuopera_wp_arg_lang(): string
{
    return defined('LANGUAGE_ID') && is_string(LANGUAGE_ID) && LANGUAGE_ID !== '' ? LANGUAGE_ID : 'ru';
}

function uuopera_wp_normalize_table_prefix(string $raw): string
{
    $s = trim($raw);
    if ($s === '') {
        return 'wp_';
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $s)) {
        fwrite(STDERR, "WP_TABLE_PREFIX invalid\n");
        exit(1);
    }
    return str_ends_with($s, '_') ? $s : $s . '_';
}

function uuopera_wp_slug_for_code(string $postName, string $title, int $wpId): string
{
    $postName = trim($postName);
    if ($postName !== '') {
        $t = strtolower($postName);
        $t = str_replace('.', '-', $t);
        if (preg_match('/^[a-z0-9_-]+$/', $t)) {
            return $t;
        }
    }
    $base = $title !== '' ? $title : 'wp-' . $wpId;
    if (class_exists('CUtil')) {
        $t = (string) \CUtil::translit($base, uuopera_wp_arg_lang(), [
            'max_len' => 100,
            'change_case' => 'L',
            'replace_space' => '-',
            'replace_other' => '-',
        ]);
        $t = trim($t, '-_');
        if ($t !== '') {
            return $t;
        }
    }
    return 'wp-' . $wpId;
}

function uuopera_wp_unique_element_code(int $iblockId, string $want, int $selfElementId): string
{
    $want = strtolower(trim($want));
    $want = preg_replace('/[^a-z0-9_-]/', '-', $want) ?? '';
    $want = trim((string) preg_replace('/-+/', '-', $want), '-');
    if ($want === '') {
        $want = 'item';
    }
    $candidate = $want;
    $n = 0;
    while (true) {
    $res = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            '=CODE' => $candidate,
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        ['nTopCount' => 1],
        ['ID']
    );
    $row = $res ? $res->Fetch() : false;
        if (!is_array($row)) {
            return $candidate;
        }
        $eid = (int) ($row['ID'] ?? 0);
        if ($selfElementId > 0 && $eid === $selfElementId) {
            return $candidate;
        }
        $n++;
        $candidate = $want . '-u' . $n;
        if ($n > 9999) {
            return $want . '-' . bin2hex(random_bytes(4));
        }
    }
}

/**
 * @param array<int, array{post_parent: int, post_name: string, post_title: string}> $map
 */
function uuopera_wp_request_path_page(array $map, int $pageId, int $frontStaticId, array &$memo, array &$guard): string
{
    if (isset($memo[$pageId])) {
        return $memo[$pageId];
    }
    if ($frontStaticId > 0 && $pageId === $frontStaticId) {
        return $memo[$pageId] = '/';
    }
    if ($guard[$pageId] ?? false) {
        return $memo[$pageId] = '/wp-page-cycle-' . $pageId;
    }
    $guard[$pageId] = true;

    $row = $map[$pageId] ?? null;
    if (!is_array($row)) {
        return $memo[$pageId] = '/wp-page-missing-' . $pageId;
    }
    $slug = trim((string) ($row['post_name'] ?? ''));
    if ($slug === '') {
        $slug = uuopera_wp_slug_for_code('', (string) ($row['post_title'] ?? ''), $pageId);
    }
    $parent = (int) ($row['post_parent'] ?? 0);
    if ($parent <= 0) {
        return $memo[$pageId] = '/' . $slug;
    }
    $base = uuopera_wp_request_path_page($map, $parent, $frontStaticId, $memo, $guard);
    if ($base === '/') {
        return $memo[$pageId] = '/' . $slug;
    }
    return $memo[$pageId] = $base . '/' . $slug;
}

function uuopera_wp_option(mysqli $db, string $optionsTable, string $name): string
{
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $optionsTable) ?? '';
    if ($safe === '' || $safe !== $optionsTable) {
        return '';
    }
    $sql = 'SELECT option_value FROM ' . $safe . ' WHERE option_name = ? LIMIT 1';
    $st = $db->prepare($sql);
    if (!$st) {
        return '';
    }
    $st->bind_param('s', $name);
    $st->execute();
    $rs = $st->get_result();
    $row = $rs ? $rs->fetch_assoc() : null;
    return is_array($row) ? (string) ($row['option_value'] ?? '') : '';
}

function uuopera_wp_attachment_url(mysqli $db, string $postsTable, int $attachmentId): string
{
    if ($attachmentId <= 0) {
        return '';
    }
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $postsTable) ?? '';
    if ($safe === '' || $safe !== $postsTable) {
        return '';
    }
    $sql = 'SELECT guid FROM ' . $safe . ' WHERE ID = ? AND post_type = ? LIMIT 1';
    $st = $db->prepare($sql);
    if (!$st) {
        return '';
    }
    $type = 'attachment';
    $st->bind_param('is', $attachmentId, $type);
    $st->execute();
    $rs = $st->get_result();
    $row = $rs ? $rs->fetch_assoc() : null;
    $g = is_array($row) ? trim((string) ($row['guid'] ?? '')) : '';
    return $g;
}

function uuopera_wp_thumb_id_for_post(mysqli $db, string $metaTable, int $postId): int
{
    if ($postId <= 0) {
        return 0;
    }
    $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $metaTable) ?? '';
    if ($safe === '' || $safe !== $metaTable) {
        return 0;
    }
    $sql = 'SELECT meta_value FROM ' . $safe . ' WHERE post_id = ? AND meta_key = ? LIMIT 1';
    $st = $db->prepare($sql);
    if (!$st) {
        return 0;
    }
    $mk = '_thumbnail_id';
    $st->bind_param('is', $postId, $mk);
    $st->execute();
    $rs = $st->get_result();
    $row = $rs ? $rs->fetch_assoc() : null;
    if (!is_array($row)) {
        return 0;
    }
    $v = trim((string) ($row['meta_value'] ?? ''));
    return $v !== '' ? (int) $v : 0;
}

function uuopera_wp_file_array_from_url(string $url): array
{
    if ($url === '') {
        return [];
    }
    if (!class_exists('CFile')) {
        return [];
    }
    $ar = \CFile::MakeFileArray($url);
    return is_array($ar) ? $ar : [];
}

function uuopera_wp_active_from(string $mysqlDate): string
{
    $t = strtotime($mysqlDate);
    if ($t === false) {
        return '';
    }
    if (function_exists('ConvertTimeStamp')) {
        return (string) ConvertTimeStamp($t, 'FULL');
    }
    return date('d.m.Y H:i:s', $t);
}

/**
 * @param array<string, mixed> $fileArray
 * @param array<string, mixed> $fields
 */
function uuopera_wp_merge_pictures(array $fileArray, array &$fields): void
{
    if ($fileArray === []) {
        return;
    }
    if (!empty($fileArray['error'])) {
        return;
    }
    if (empty($fileArray['tmp_name']) || !is_file((string) $fileArray['tmp_name'])) {
        return;
    }
    $fileArray['MODULE_ID'] = 'iblock';
    $fields['PREVIEW_PICTURE'] = $fileArray;
    $fields['DETAIL_PICTURE'] = $fileArray;
}

/**
 * @param array<string, string> $props
 */
function uuopera_wp_save_element(int $iblockId, string $xmlId, array $fields, array $props, bool $dry): int
{
    if ($iblockId <= 0 || $xmlId === '') {
        return 0;
    }
    $res = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $iblockId,
            '=XML_ID' => $xmlId,
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'CODE']
    );
    $ex = $res ? $res->Fetch() : false;
    $selfId = is_array($ex) ? (int) ($ex['ID'] ?? 0) : 0;
    $baseCode = (string) ($fields['CODE'] ?? '');
    if ($selfId > 0) {
        $keep = trim((string) ($ex['CODE'] ?? ''));
        if ($keep !== '') {
            $fields['CODE'] = $keep;
        } else {
            $fields['CODE'] = uuopera_wp_unique_element_code($iblockId, $baseCode, $selfId);
        }
    } else {
        $fields['CODE'] = uuopera_wp_unique_element_code($iblockId, $baseCode, 0);
    }
    $fields['XML_ID'] = $xmlId;
    if ($dry) {
        return $selfId > 0 ? $selfId : -1;
    }
    global $APPLICATION;
    $el = new CIBlockElement();
    if ($selfId > 0) {
        $ok = $el->Update($selfId, $fields);
        if (!$ok) {
            $msg = is_object($APPLICATION) && method_exists($APPLICATION, 'GetException') && $APPLICATION->GetException()
                ? $APPLICATION->GetException()->GetString() : '?';
            fwrite(STDERR, 'Update ID ' . $selfId . ' XML ' . $xmlId . ' ' . $msg . "\n");
            return 0;
        }
        CIBlockElement::SetPropertyValuesEx($selfId, $iblockId, $props);
        return $selfId;
    }
    $newId = (int) $el->Add($fields);
    if ($newId <= 0) {
        $msg = is_object($APPLICATION) && method_exists($APPLICATION, 'GetException') && $APPLICATION->GetException()
            ? $APPLICATION->GetException()->GetString() : '?';
        fwrite(STDERR, 'Add XML ' . $xmlId . ' ' . $msg . "\n");
        return 0;
    }
    CIBlockElement::SetPropertyValuesEx($newId, $iblockId, $props);
    return $newId;
}

$dry = false;
$withThumb = false;
$only = ['pages' => true, 'posts' => true, 'persone' => true, 'projects' => true];
foreach (array_slice($argv, 1) as $raw) {
    if ($raw === '--dry-run') {
        $dry = true;
        continue;
    }
    if ($raw === '--with-thumb') {
        $withThumb = true;
        continue;
    }
    if (str_starts_with($raw, '--only=')) {
        $list = strtolower(trim(substr($raw, 7)));
        $only = [];
        foreach (explode(',', $list, 20) as $p) {
            $p = trim($p);
            if ($p !== '') {
                $only[$p] = true;
            }
        }
        if ($only === []) {
            fwrite(STDERR, "--only empty\n");
            exit(1);
        }
        continue;
    }
    fwrite(STDERR, "Unknown arg: {$raw}\n");
    exit(1);
}

$host = getenv('WP_IMPORT_DB_HOST');
if (!$host || $host === '') {
    $host = 'db';
}
$dbName = getenv('WP_IMPORT_DB_NAME');
if (!$dbName || $dbName === '') {
    $dbName = 'wordpress';
}
$user = getenv('WP_IMPORT_DB_USER');
if (!$user || $user === '') {
    $mu = getenv('MYSQL_USER');
    $user = ($mu !== false && $mu !== '') ? $mu : 'bitrix';
}
$wpPass = getenv('WP_IMPORT_DB_PASSWORD');
$password = $wpPass !== false && $wpPass !== '' ? $wpPass : (string) (getenv('MYSQL_PASSWORD') ?: '');

$tpr = getenv('WP_TABLE_PREFIX') ?: getenv('TABLE_PREFIX_WORDPRESS');
$tblBase = uuopera_wp_normalize_table_prefix(is_string($tpr) ? $tpr : '');
$pPosts = $tblBase . 'posts';
$pOpts = $tblBase . 'options';
$pMeta = $tblBase . 'postmeta';
if ($pPosts !== preg_replace('/[^a-zA-Z0-9_]/', '', $pPosts)) {
    fwrite(STDERR, "posts table alias invalid\n");
    exit(1);
}

$wp = @new mysqli((string) $host, (string) $user, $password, (string) $dbName);
if ($wp->connect_errno) {
    fwrite(STDERR, 'WP mysqli connect: ' . $wp->connect_error . "\n");
    exit(1);
}
$wp->set_charset('utf8mb4');

$showFront = uuopera_wp_option($wp, $pOpts, 'show_on_front');
$pageOnFront = (int) uuopera_wp_option($wp, $pOpts, 'page_on_front');
$frontStaticId = ($showFront === 'page' && $pageOnFront > 0) ? $pageOnFront : 0;

$staticId = uuopera_cms_static_pages_iblock_id();
$newsId = uuopera_news_iblock_id();
$projId = uuopera_cms_projects_iblock_id();

$counters = ['pages_ok' => 0, 'posts_ok' => 0, 'persone_ok' => 0, 'projects_ok' => 0];

if (($only['pages'] ?? false)) {
    if ($staticId <= 0) {
        fwrite(STDERR, "pages: cms_static_pages_iblock_id=0, запустите uuopera_cms_iblocks_install.php\n");
    } else {
    $map = [];
    $stmt = $wp->prepare('SELECT ID, post_parent, post_name, post_title, post_password, post_content, menu_order FROM ' . $pPosts . " WHERE post_type = 'page' AND post_status = 'publish' ORDER BY menu_order ASC, ID ASC");
    if (!$stmt || !$stmt->execute()) {
        fwrite(STDERR, "WP pages query failed\n");
        exit(1);
    }
    $qrs = $stmt->get_result();
    while ($r = $qrs->fetch_assoc()) {
        $id = (int) ($r['ID'] ?? 0);
        $map[$id] = [
            'post_parent' => (int) ($r['post_parent'] ?? 0),
            'post_name' => (string) ($r['post_name'] ?? ''),
            'post_title' => (string) ($r['post_title'] ?? ''),
            'post_password' => (string) ($r['post_password'] ?? ''),
            'post_content' => (string) ($r['post_content'] ?? ''),
            'menu_order' => (int) ($r['menu_order'] ?? 500),
        ];
    }
    $stmt->close();
    $memo = [];
    $seenPaths = [];
    foreach ($map as $pid => $_row) {
        if ($_row['post_password'] !== '') {
            continue;
        }
        $g = [];
        $path = uuopera_wp_request_path_page($map, $pid, $frontStaticId, $memo, $g);
        $path = uuopera_cms_normalize_request_path($path);
        if (isset($seenPaths[$path])) {
            fwrite(STDERR, 'Duplicate REQUEST_PATH skipped page ID ' . $pid . "\n");
            continue;
        }
        $seenPaths[$path] = true;
        $thumb = '';
        if ($withThumb && $staticId > 0) {
            $tid = uuopera_wp_thumb_id_for_post($wp, $pMeta, $pid);
            $thumb = uuopera_wp_attachment_url($wp, $pPosts, $tid);
        }
        $props = ['REQUEST_PATH' => $path, 'HEADER_SCHEMA' => 'beige'];
        $sort = ($_row['menu_order'] ?: 500) + 300;
        $xml = 'uuopera_wp_page_' . $pid;
        $fields = [
            'MODIFIED_BY' => 1,
            'IBLOCK_SECTION_ID' => false,
            'IBLOCK_ID' => $staticId,
            'NAME' => ($_row['post_title'] ?: $path),
            'CODE' => 'pg-' . $pid,
            'ACTIVE' => 'Y',
            'SORT' => $sort,
            'PREVIEW_TEXT' => '',
            'PREVIEW_TEXT_TYPE' => 'text',
            'DETAIL_TEXT' => $_row['post_content'],
            'DETAIL_TEXT_TYPE' => 'html',
        ];
        $fa = ($withThumb && $thumb !== '') ? uuopera_wp_file_array_from_url($thumb) : [];
        uuopera_wp_merge_pictures($fa, $fields);
        $eid = uuopera_wp_save_element($staticId, $xml, $fields, $props, $dry);
        if ($dry ? true : $eid > 0) {
            $counters['pages_ok']++;
        }
    }
    }
}

if (($only['persone'] ?? false) && $staticId <= 0) {
    fwrite(STDERR, "persone: cms_static_pages_iblock_id=0, нужен uuopera_cms_iblocks_install.php\n");
}

if (($only['posts'] ?? false) && $newsId <= 0) {
    fwrite(STDERR, "posts: uuopera_news_iblock_id не задан (option news_iblock_id)\n");
}
if (($only['projects'] ?? false) && $projId <= 0) {
    fwrite(STDERR, "projects: cms_projects_iblock_id=0, нужен uuopera_cms_iblocks_install.php\n");
}
$postTypes = [];
if (($only['posts'] ?? false)) {
    $postTypes[] = 'post';
}
if (($only['persone'] ?? false)) {
    $postTypes[] = 'persone';
}
if (($only['projects'] ?? false)) {
    $postTypes[] = 'project';
}

foreach ($postTypes as $ptype) {
    $st = $wp->prepare('SELECT ID, post_date, post_title, post_content, post_excerpt, post_name, post_password FROM ' . $pPosts . ' WHERE post_type = ? AND post_status = ? ORDER BY ID ASC');
    if (!$st) {
        fwrite(STDERR, "prepare failed {$ptype}\n");
        exit(1);
    }
    $pub = 'publish';
    $st->bind_param('ss', $ptype, $pub);
    $st->execute();
    $rs = $st->get_result();
    while ($row = $rs->fetch_assoc()) {
        $wid = (int) ($row['ID'] ?? 0);
        if (($row['post_password'] ?? '') !== '') {
            continue;
        }
        $title = (string) ($row['post_title'] ?? '');
        $content = (string) ($row['post_content'] ?? '');
        $excerpt = (string) ($row['post_excerpt'] ?? '');
        $slug = (string) ($row['post_name'] ?? '');
        $code = uuopera_wp_slug_for_code($slug, $title, $wid);
        $xml = 'uuopera_wp_' . $ptype . '_' . $wid;
        $thumbUrl = '';
        if ($withThumb) {
            $tid = uuopera_wp_thumb_id_for_post($wp, $pMeta, $wid);
            $thumbUrl = uuopera_wp_attachment_url($wp, $pPosts, $tid);
        }
        $fa = ($withThumb && $thumbUrl !== '') ? uuopera_wp_file_array_from_url($thumbUrl) : [];

        if ($ptype === 'post') {
            if ($newsId <= 0) {
                continue;
            }
            $af = uuopera_wp_active_from((string) ($row['post_date'] ?? ''));
            $props = [];
            $fields = [
                'MODIFIED_BY' => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID' => $newsId,
                'NAME' => $title !== '' ? $title : $code,
                'CODE' => $code,
                'ACTIVE' => 'Y',
                'SORT' => 500,
                'DATE_ACTIVE_FROM' => $af,
                'PREVIEW_TEXT' => $excerpt,
                'PREVIEW_TEXT_TYPE' => 'html',
                'DETAIL_TEXT' => $content,
                'DETAIL_TEXT_TYPE' => 'html',
            ];
            uuopera_wp_merge_pictures($fa, $fields);
            $eid = uuopera_wp_save_element($newsId, $xml, $fields, $props, $dry);
            if ($dry ? true : $eid > 0) {
                $counters['posts_ok']++;
            }
            continue;
        }

        if ($ptype === 'persone') {
            if ($staticId <= 0) {
                continue;
            }
            $path = '/persone/' . $code;
            $path = uuopera_cms_normalize_request_path($path);
            $props = ['REQUEST_PATH' => $path, 'HEADER_SCHEMA' => 'beige'];
            $fields = [
                'MODIFIED_BY' => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID' => $staticId,
                'NAME' => $title !== '' ? $title : $path,
                'CODE' => 'persone-' . $wid,
                'ACTIVE' => 'Y',
                'SORT' => 500 + $wid % 999,
                'PREVIEW_TEXT' => '',
                'PREVIEW_TEXT_TYPE' => 'text',
                'DETAIL_TEXT' => $content,
                'DETAIL_TEXT_TYPE' => 'html',
            ];
            uuopera_wp_merge_pictures($fa, $fields);
            $eid = uuopera_wp_save_element($staticId, $xml, $fields, $props, $dry);
            if ($dry ? true : $eid > 0) {
                $counters['persone_ok']++;
            }
            continue;
        }

        if ($ptype === 'project') {
            if ($projId <= 0) {
                continue;
            }
            $props = [
                'TEASER_HTML' => $excerpt,
            ];
            $fields = [
                'MODIFIED_BY' => 1,
                'IBLOCK_SECTION_ID' => false,
                'IBLOCK_ID' => $projId,
                'NAME' => $title !== '' ? $title : $code,
                'CODE' => $code,
                'ACTIVE' => 'Y',
                'SORT' => 400,
                'PREVIEW_TEXT' => '',
                'PREVIEW_TEXT_TYPE' => 'text',
                'DETAIL_TEXT' => $content,
                'DETAIL_TEXT_TYPE' => 'html',
            ];
            uuopera_wp_merge_pictures($fa, $fields);
            $eid = uuopera_wp_save_element($projId, $xml, $fields, $props, $dry);
            if ($dry ? true : $eid > 0) {
                $counters['projects_ok']++;
            }
        }
    }
    $st->close();
}

$wp->close();

echo 'pages(static): ' . $counters['pages_ok']
    . ' news: ' . $counters['posts_ok']
    . ' persone(static): ' . $counters['persone_ok']
    . ' projects: ' . $counters['projects_ok']
    . ($dry ? ' (dry-run)' : '') . "\n";
exit(0);
