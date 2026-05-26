<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_wp_assign_sections.php [--dry-run]
 *
 * Assigns Bitrix news iblock elements to sections based on WordPress term
 * (category) relationships. Requires that:
 *   1. wp_* tables exist in the same MariaDB database as Bitrix.
 *   2. News elements were imported with XML_ID = 'uuopera_wp_post_{wp_id}'.
 *   3. Bitrix sections have CODE values matching WP category slugs.
 *
 * Run after uuopera_wp_mysql_import_iblocks.php.
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

$newsIblockId = uuopera_news_iblock_id();
if ($newsIblockId <= 0) {
    fwrite(STDERR, "news iblock not configured — run uuopera_cms_iblocks_install.php first\n");
    exit(1);
}

// Bitrix DB connection (same DB that has wp_* tables)
/** @var \Bitrix\Main\DB\Connection $bxConn */
$bxConn = \Bitrix\Main\Application::getConnection();
$db = $bxConn->getResource();
if (!$db instanceof mysqli) {
    fwrite(STDERR, "Cannot get mysqli resource from Bitrix connection\n");
    exit(1);
}

// --- 1. Map Bitrix section CODE => section ID for the news iblock ---

$secRows = $bxConn->query(
    'SELECT ID, CODE FROM b_iblock_section WHERE IBLOCK_ID = ' . $newsIblockId . " AND ACTIVE = 'Y'"
)->fetchAll();

/** @var array<string, int> $sectionMap  slug => bitrix section ID */
$sectionMap = [];
foreach ($secRows as $r) {
    $code = trim((string) ($r['CODE'] ?? ''));
    if ($code !== '') {
        $sectionMap[$code] = (int) $r['ID'];
    }
}

if (empty($sectionMap)) {
    fwrite(STDERR, "No active sections in news iblock. Nothing to do.\n");
    exit(0);
}

echo 'Bitrix sections found: ' . implode(', ', array_map(
    fn($code, $id) => "{$code}({$id})",
    array_keys($sectionMap),
    array_values($sectionMap)
)) . "\n";

// --- 2. Fetch WP post → categories mapping (only slugs that match Bitrix sections) ---

$slugList = implode(', ', array_map(fn($s) => "'" . $db->real_escape_string($s) . "'", array_keys($sectionMap)));

$st = $db->prepare(
    'SELECT tr.object_id AS wp_post_id, t.slug AS cat_slug
     FROM wp_term_relationships tr
     JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
     JOIN wp_terms t ON t.term_id = tt.term_id
     JOIN wp_posts p ON p.ID = tr.object_id
     WHERE tt.taxonomy = \'category\'
       AND t.slug IN (' . $slugList . ')
       AND p.post_type = \'post\'
       AND p.post_status = \'publish\'
     ORDER BY tr.object_id ASC, t.slug ASC'
);
if (!$st || !$st->execute()) {
    fwrite(STDERR, "WP query failed: " . $db->error . "\n");
    exit(1);
}
$wpRows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// Group: wp_post_id => [slug, ...]  (first slug = primary if "news" exists, else first)
/** @var array<int, list<string>> $wpPostSlugs */
$wpPostSlugs = [];
foreach ($wpRows as $r) {
    $wpId  = (int) $r['wp_post_id'];
    $slug  = (string) $r['cat_slug'];
    $wpPostSlugs[$wpId][] = $slug;
}

echo 'WP posts with matching categories: ' . count($wpPostSlugs) . "\n";

if (empty($wpPostSlugs)) {
    echo "Nothing to assign.\n";
    exit(0);
}

// --- 3. Find Bitrix element IDs by XML_ID ---
// Elements imported via uuopera_wp_mysql_import_iblocks.php use XML_ID='uuopera_wp_post_{id}'.
// Elements imported via other tools (direct DB import) use XML_ID='{wp_post_id}' (plain integer).
// We handle both formats.

$wpIds = array_keys($wpPostSlugs);
$idPlaceholders = implode(', ', $wpIds);

// Fetch elements whose XML_ID is either plain wp_post_id or 'uuopera_wp_post_' + wp_post_id
$elRows = $bxConn->query(
    'SELECT ID, XML_ID FROM b_iblock_element'
    . ' WHERE IBLOCK_ID = ' . $newsIblockId
    . ' AND ('
    . '   XML_ID IN (' . $idPlaceholders . ')'
    . '   OR XML_ID IN (' . implode(', ', array_map(fn($id) => "'uuopera_wp_post_" . $id . "'", $wpIds)) . ')'
    . ' )'
)->fetchAll();

/** @var array<int, int> $xmlToElId  wp_post_id => bitrix element ID */
$xmlToElId = [];
foreach ($elRows as $r) {
    $xmlId = (string) $r['XML_ID'];
    if (preg_match('/^uuopera_wp_post_(\d+)$/', $xmlId, $m)) {
        $wpId = (int) $m[1];
    } elseif (ctype_digit($xmlId)) {
        $wpId = (int) $xmlId;
    } else {
        continue;
    }
    if (isset($wpPostSlugs[$wpId])) {
        $xmlToElId[$wpId] = (int) $r['ID'];
    }
}

echo 'Bitrix elements found: ' . count($xmlToElId) . "\n";

// --- 4. Build primary section assignment and multi-section pairs ---

/** @var array<int, int> $elPrimarySection  bitrix_el_id => primary bitrix_section_id */
$elPrimarySection = [];
/** @var list<array{int, int}> $sectionElementPairs  [section_id, el_id] */
$sectionElementPairs = [];

foreach ($wpPostSlugs as $wpId => $slugs) {
    $elId = $xmlToElId[$wpId] ?? 0;
    if ($elId <= 0) {
        continue;
    }
    // Primary: prefer 'news', otherwise first slug
    $primarySlug = in_array('news', $slugs, true) ? 'news' : $slugs[0];
    $primarySecId = $sectionMap[$primarySlug] ?? 0;
    if ($primarySecId > 0) {
        $elPrimarySection[$elId] = $primarySecId;
    }
    foreach ($slugs as $slug) {
        $secId = $sectionMap[$slug] ?? 0;
        if ($secId > 0) {
            $sectionElementPairs[] = [$secId, $elId];
        }
    }
}

echo 'Elements to update: ' . count($elPrimarySection) . "\n";
echo 'Section-element pairs to insert: ' . count($sectionElementPairs) . "\n";

if ($dry) {
    echo "(dry-run) no changes written\n";
    exit(0);
}

// --- 5. Update primary IBLOCK_SECTION_ID in b_iblock_element ---

$updated = 0;
foreach ($elPrimarySection as $elId => $secId) {
    $bxConn->query(
        'UPDATE b_iblock_element SET IBLOCK_SECTION_ID = ' . $secId
        . ' WHERE ID = ' . $elId . ' AND IBLOCK_ID = ' . $newsIblockId
    );
    $updated++;
}
echo 'Updated IBLOCK_SECTION_ID: ' . $updated . "\n";

// --- 6. Insert into b_iblock_section_element (replace existing to avoid dupes) ---

if (!empty($sectionElementPairs)) {
    // Clear old section links for these elements first
    $elIds = array_unique(array_map(fn($pair) => $pair[1], $sectionElementPairs));
    $elIdList = implode(', ', $elIds);
    $bxConn->query(
        'DELETE FROM b_iblock_section_element WHERE IBLOCK_ELEMENT_ID IN (' . $elIdList . ')'
    );

    // Insert in batches of 500
    $chunks = array_chunk($sectionElementPairs, 500);
    foreach ($chunks as $chunk) {
        $vals = implode(', ', array_map(fn($p) => '(' . $p[0] . ', ' . $p[1] . ', NULL)', $chunk));
        $bxConn->query(
            'INSERT INTO b_iblock_section_element (IBLOCK_SECTION_ID, IBLOCK_ELEMENT_ID, ADDITIONAL_PROPERTY_ID) VALUES ' . $vals
        );
    }
    echo 'Section-element links inserted: ' . count($sectionElementPairs) . "\n";
}

echo "Done.\n";
exit(0);
