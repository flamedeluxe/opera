<?php

/**
 * Импорт контента из WordPress БД в Bitrix инфоблоки.
 * CLI: php local/tools/uuopera_wp_import.php [--type=events|news|all]
 * Требования: WP-таблицы залиты в ту же БД; /var/www/wp-content/uploads/ существует.
 */

declare(strict_types=1);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "DOCUMENT_ROOT not found\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_BUFFER_USED', true);
define('BX_NO_ACCELERATOR_RESET', true);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (PHP_SAPI !== 'cli') {
    global $USER;
    if (!is_object($USER) || !$USER->IsAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Только для администратора.';
        exit;
    }
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    echo "Модуль iblock не установлен.\n";
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events_bootstrap.php';

$importType = 'all';
foreach (array_slice($argv ?? [], 1) as $arg) {
    if (preg_match('/^--type=(\w+)$/', $arg, $m)) {
        $importType = $m[1];
    }
}

try {
    $struct = uuopera_afisha_events_bootstrap_iblock();
} catch (Throwable $e) {
    echo "Ошибка инфоблока: " . $e->getMessage() . "\n";
    exit(1);
}
$bid = $struct['iblock_id'];
echo "Инфоблок афиши ID={$bid}\n";

$newsBid = uuopera_news_iblock_id();
echo "Инфоблок новостей ID={$newsBid}\n";

$db = \Bitrix\Main\Application::getConnection();

define('WP_UPLOADS_BASE', $_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads');
define('WP_SITE_URL', 'https://uuopera.ru');

$ruMonths = [
    1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
    5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
    9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
];

$sectionTitles = [
    'opera'        => 'Опера',
    'ballet'       => 'Балет',
    'concert'      => 'Концерты',
    'excursions'   => 'Экскурсии',
    'festivals'    => 'Фестивали',
    'online'       => 'Онлайн',
    'performances' => 'Представления',
    'musical'      => 'Мюзикл',
    'fairytale'    => 'Музыкальная сказка',
    'operetta'     => 'Оперетта',
    'abonement'    => 'Абонемент',
    'no-category'  => 'Без категории',
    'news'         => 'Новости',
];

function wp_date_to_session_label(string $date, array $ruMonths): string
{
    $ts = strtotime($date);
    if ($ts === false) return $date;
    return date('j', $ts) . ' ' . $ruMonths[(int) date('n', $ts)] . ' ' . date('H:i', $ts);
}

function wp_url_to_local_path(string $url): string
{
    $prefix = WP_SITE_URL . '/wp-content/uploads/';
    if (str_starts_with($url, $prefix)) {
        return WP_UPLOADS_BASE . '/' . substr($url, strlen($prefix));
    }
    if (str_starts_with($url, '/wp-content/uploads/')) {
        return $_SERVER['DOCUMENT_ROOT'] . $url;
    }
    return $url;
}

function bx_last_error(): string
{
    global $APPLICATION;
    $e = $APPLICATION->GetException();
    return $e ? $e->GetString() : '?';
}

function build_sessions_json(array $meta, array $ruMonths): string
{
    $count = (int) ($meta['seances'] ?? 0);
    if ($count <= 0) {
        if (!empty($meta['seance_date'])) {
            return json_encode([[wp_date_to_session_label($meta['seance_date'], $ruMonths), '']], JSON_UNESCAPED_UNICODE);
        }
        return '[]';
    }
    $sessions = [];
    for ($i = 0; $i < $count; $i++) {
        $date = $meta["seances_{$i}_date"] ?? '';
        if ($date === '') continue;
        $sessions[] = [wp_date_to_session_label($date, $ruMonths), (string) ($meta["seances_{$i}_intickets_seance_id"] ?? '')];
    }
    return json_encode($sessions, JSON_UNESCAPED_UNICODE);
}

$sectionCache = [];
function get_or_create_section(int $bid, string $code, array $sectionTitles, array &$cache): int
{
    if (!isset($cache[$code])) {
        $cache[$code] = uuopera_afisha_events_ensure_section($bid, $code, $sectionTitles[$code] ?? $code);
    }
    return $cache[$code];
}

/**
 * One query for all postmeta of given posts. Returns postId → [key => value].
 * Keeps _thumbnail_id, skips other ACF pointer keys (start with _).
 *
 * @param int[] $postIds
 * @return array<int, array<string, string>>
 */
function batch_load_postmeta(array $postIds, \Bitrix\Main\DB\Connection $db): array
{
    if ($postIds === []) return [];
    $ids = implode(',', array_map('intval', $postIds));
    $res = $db->query("SELECT post_id, meta_key, meta_value FROM wp_postmeta WHERE post_id IN ({$ids})");
    $out = [];
    while ($row = $res->fetch()) {
        $key = $row['meta_key'];
        if (str_starts_with($key, '_') && $key !== '_thumbnail_id') continue;
        $out[(int) $row['post_id']][$key] = $row['meta_value'];
    }
    return $out;
}

/**
 * One query to resolve _thumbnail_id → attachment guid URL for all posts.
 *
 * @param array<int, array<string, string>> $allMeta
 * @return array<int, string>  postId → URL
 */
function batch_load_thumbnails(array $allMeta, \Bitrix\Main\DB\Connection $db): array
{
    $postToAtt = [];
    foreach ($allMeta as $postId => $meta) {
        $attId = (int) ($meta['_thumbnail_id'] ?? 0);
        if ($attId > 0) $postToAtt[$postId] = $attId;
    }
    if ($postToAtt === []) return [];
    $ids = implode(',', array_unique(array_values($postToAtt)));
    $res = $db->query("SELECT ID, guid FROM wp_posts WHERE ID IN ({$ids})");
    $attGuids = [];
    while ($row = $res->fetch()) {
        $attGuids[(int) $row['ID']] = $row['guid'];
    }
    $out = [];
    foreach ($postToAtt as $postId => $attId) {
        if (isset($attGuids[$attId])) $out[$postId] = $attGuids[$attId];
    }
    return $out;
}

/**
 * One query for primary event_category slug for all given posts.
 *
 * @param int[] $postIds
 * @return array<int, string>  postId → slug
 */
function batch_load_event_categories(array $postIds, \Bitrix\Main\DB\Connection $db): array
{
    if ($postIds === []) return [];
    $ids = implode(',', array_map('intval', $postIds));
    $res = $db->query("
        SELECT tr.object_id, t.slug
        FROM wp_terms t
        JOIN wp_term_taxonomy tt ON t.term_id = tt.term_id
        JOIN wp_term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
        WHERE tr.object_id IN ({$ids}) AND tt.taxonomy = 'event_category'
    ");
    $out = [];
    while ($row = $res->fetch()) {
        $out[(int) $row['object_id']] = $row['slug'];
    }
    return $out;
}

/**
 * Pre-fetch existing Bitrix element CODEs to avoid per-row GetList calls.
 *
 * @return array<string, int>  code → element ID
 */
function fetch_existing_codes(int $bid, \Bitrix\Main\DB\Connection $db): array
{
    $res = $db->query("SELECT ID, CODE FROM b_iblock_element WHERE IBLOCK_ID = {$bid} AND CODE != '' AND CODE IS NOT NULL");
    $out = [];
    while ($row = $res->fetch()) {
        $out[$row['CODE']] = (int) $row['ID'];
    }
    return $out;
}

/** @return array{0: int, 1: int}  [ok, fail] */
function import_batch(
    array $posts,
    int $bid,
    array $allMeta,
    array $thumbs,
    array $cats,
    string $defaultCategory,
    array &$existingCodes,
    array $sectionTitles,
    array $ruMonths,
    array &$sectionCache
): array {
    $ok = 0;
    $fail = 0;
    $el = new CIBlockElement();

    foreach ($posts as $postId => $post) {
        $title    = (string) $post['post_title'];
        $content  = (string) $post['post_content'];
        $excerpt  = (string) $post['post_excerpt'];
        $postName = preg_replace('/[^a-z0-9_-]/i', '-', (string) $post['post_name']) ?? 'post-' . $postId;
        $postDate = (string) $post['post_date'];
        $meta     = $allMeta[$postId] ?? [];
        $category = $cats[$postId] ?? $defaultCategory;

        // For events use first seance date as ACTIVE_FROM so afisha sorts by actual show date
        // Bitrix expects d.m.Y H:i:s; MySQL-format dates from WP cause Update() to silently fail
        $firstSeanceDate = $meta['seances_0_date'] ?? $meta['seance_date'] ?? '';
        $rawFrom = ($firstSeanceDate !== '' && $category !== 'news') ? $firstSeanceDate : $postDate;
        $ts = strtotime($rawFrom);
        $activeFrom = $ts !== false ? date('d.m.Y H:i:s', $ts) : $rawFrom;

        $sid = get_or_create_section($bid, $category, $sectionTitles, $sectionCache);
        if ($sid <= 0) {
            echo "  FAIL #{$postId}: раздел '{$category}'\n";
            $fail++;
            continue;
        }

        $radarioAfishaKey = trim((string) ($meta['radario_afisha_key'] ?? ''));
        $inticketsId      = trim((string) ($meta['intickets_id'] ?? ''));
        $radarioHeroMode  = $radarioAfishaKey !== '' ? 'afisha' : ($inticketsId !== '' ? 'event' : '');

        $descText = trim((string) ($meta['description'] ?? ''));
        if ($descText !== '') {
            $paragraphs = preg_split('/\n{2,}/', $descText) ?: [];
            $descHtml   = implode('', array_map(static function (string $p): string {
                $p = trim($p);
                return $p !== '' ? '<p>' . nl2br(htmlspecialchars($p, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</p>' : '';
            }, $paragraphs));
        } else {
            $descHtml = $content;
        }

        $previewHtml = trim((string) ($meta['description_preview'] ?? $excerpt));
        if ($previewHtml !== '') {
            $previewHtml = '<p>' . htmlspecialchars($previewHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
        }

        $summaryHtml = trim((string) ($meta['summary'] ?? ''));

        $props = [
            'CATEGORY'             => $category,
            'LAYOUT'               => 'event',
            'AGE'                  => (string) ($meta['age'] ?? ''),
            'SESSIONS_JSON'        => build_sessions_json($meta, $ruMonths),
            'PARTICIPANTS_HTML'    => (string) ($meta['participants'] ?? ''),
            'PUSHKIN_CARD'         => !empty($meta['pushkin_card']) ? 'Y' : '',
            'RADARIO_AFISHA_KEY'   => $radarioAfishaKey,
            'RADARIO_HERO_EVENT_ID'=> $inticketsId,
            'RADARIO_HERO_MODE'    => $radarioHeroMode,
            'HERO_META_HTML'       => $summaryHtml,
            'SOURCE_URL'           => WP_SITE_URL . '/' . $postName . '/',
        ];

        $fields = [
            'NAME'              => $title,
            'CODE'              => $postName,
            'ACTIVE'            => 'Y',
            'IBLOCK_ID'         => $bid,
            'IBLOCK_SECTION_ID' => $sid,
            'DETAIL_TEXT'       => $descHtml,
            'DETAIL_TEXT_TYPE'  => 'html',
            'PREVIEW_TEXT'      => $previewHtml,
            'PREVIEW_TEXT_TYPE' => 'html',
            'DATE_CREATE'       => $postDate,
            'TIMESTAMP_X'       => $postDate,
            'ACTIVE_FROM'       => $activeFrom,
        ];

        $thumbUrl = $thumbs[$postId] ?? '';
        $fa = $thumbUrl !== '' ? CFile::MakeFileArray(wp_url_to_local_path($thumbUrl)) : null;
        if ($fa) {
            $fa['MODULE_ID'] = 'iblock';
            $fields['PREVIEW_PICTURE'] = $fa;
        }

        $existingId = $existingCodes[$postName] ?? null;
        if ($existingId !== null) {
            unset($fields['IBLOCK_ID']);
            if (!$el->Update($existingId, $fields)) {
                echo "  FAIL update #{$postId} «{$title}»: " . bx_last_error() . "\n";
                $fail++;
                continue;
            }
            CIBlockElement::SetPropertyValuesEx($existingId, $bid, $props);
            echo "  UPDATE ID={$existingId} «{$title}»\n";
        } else {
            $newId = (int) $el->Add($fields);
            if ($newId <= 0) {
                echo "  FAIL add #{$postId} «{$title}»: " . bx_last_error() . "\n";
                $fail++;
                continue;
            }
            CIBlockElement::SetPropertyValuesEx($newId, $bid, $props);
            $existingCodes[$postName] = $newId;
            echo "  ADD ID={$newId} «{$title}»\n";
        }
        $ok++;
    }

    return [$ok, $fail];
}

function load_wp_posts(string $postType, \Bitrix\Main\DB\Connection $db): array
{
    $type = $db->getSqlHelper()->forSql($postType);
    $res  = $db->query("
        SELECT ID, post_title, post_name, post_content, post_excerpt, post_date
        FROM wp_posts
        WHERE post_type = '{$type}' AND post_status = 'publish'
        ORDER BY post_date DESC
    ");
    $out = [];
    while ($row = $res->fetch()) {
        $out[(int) $row['ID']] = $row;
    }
    return $out;
}

$existingCodes = fetch_existing_codes($bid, $db);
echo "Существующих элементов в Bitrix: " . count($existingCodes) . "\n";

if (in_array($importType, ['events', 'all'])) {
    echo "\n=== Импорт событий афиши (event) ===\n";
    $posts   = load_wp_posts('event', $db);
    $postIds = array_keys($posts);
    $allMeta = batch_load_postmeta($postIds, $db);
    $thumbs  = batch_load_thumbnails($allMeta, $db);
    $cats    = batch_load_event_categories($postIds, $db);
    [$ok, $fail] = import_batch($posts, $bid, $allMeta, $thumbs, $cats, 'no-category', $existingCodes, $sectionTitles, $ruMonths, $sectionCache);
    echo "События: OK={$ok} FAIL={$fail}\n";
}

if (in_array($importType, ['news', 'all'])) {
    echo "\n=== Импорт новостей (post → инфоблок новостей ID={$newsBid}) ===\n";
    if ($newsBid <= 0) {
        echo "  SKIP: инфоблок новостей не найден (news_iblock_id=0)\n";
    } else {
        $posts        = load_wp_posts('post', $db);
        $postIds      = array_keys($posts);
        $allMeta      = batch_load_postmeta($postIds, $db);
        $thumbs       = batch_load_thumbnails($allMeta, $db);
        $newsExisting = fetch_existing_codes($newsBid, $db);
        $el           = new CIBlockElement();
        $ok = 0; $fail = 0;
        foreach ($posts as $postId => $post) {
            $title    = (string) $post['post_title'];
            $postName = preg_replace('/[^a-z0-9_-]/i', '-', (string) $post['post_name']) ?? 'post-' . $postId;
            $postDate = (string) $post['post_date'];
            $ts       = strtotime($postDate);
            $activeFrom = $ts !== false ? date('d.m.Y H:i:s', $ts) : $postDate;

            $thumbUrl = $thumbs[$postId] ?? '';
            $fa = $thumbUrl !== '' ? CFile::MakeFileArray(wp_url_to_local_path($thumbUrl)) : null;

            $fields = [
                'NAME'             => $title,
                'CODE'             => $postName,
                'ACTIVE'           => 'Y',
                'IBLOCK_ID'        => $newsBid,
                'DETAIL_TEXT'      => (string) $post['post_content'],
                'DETAIL_TEXT_TYPE' => 'html',
                'PREVIEW_TEXT'     => (string) $post['post_excerpt'],
                'PREVIEW_TEXT_TYPE'=> 'html',
                'DATE_CREATE'      => $postDate,
                'TIMESTAMP_X'      => $postDate,
                'ACTIVE_FROM'      => $activeFrom,
            ];
            if ($fa) {
                $fa['MODULE_ID'] = 'iblock';
                $fields['PREVIEW_PICTURE'] = $fa;
            }

            $existingId = $newsExisting[$postName] ?? null;
            if ($existingId !== null) {
                unset($fields['IBLOCK_ID']);
                if (!$el->Update($existingId, $fields)) {
                    echo "  FAIL update #{$postId} «{$title}»: " . bx_last_error() . "\n";
                    $fail++;
                    continue;
                }
                echo "  UPDATE ID={$existingId} «{$title}»\n";
            } else {
                $newId = (int) $el->Add($fields);
                if ($newId <= 0) {
                    echo "  FAIL add #{$postId} «{$title}»: " . bx_last_error() . "\n";
                    $fail++;
                    continue;
                }
                $newsExisting[$postName] = $newId;
                echo "  ADD ID={$newId} «{$title}»\n";
            }
            $ok++;
        }
        echo "Новости: OK={$ok} FAIL={$fail}\n";
    }
}

echo "\nИмпорт завершён. Events IBLOCK_ID={$bid}, News IBLOCK_ID={$newsBid}\n";
