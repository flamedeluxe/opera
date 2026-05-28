<?php

declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_news_sections_seed.php
 *
 * Creates sections in the news iblock matching WordPress category slugs,
 * then assigns imported news elements (by XML_ID prefix) to their sections
 * based on wp_term_relationships from the WordPress dump.
 *
 * Safe to re-run: skips existing sections and already-assigned elements.
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

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module missing\n");
    exit(1);
}

$iblockId = (int) \Bitrix\Main\Config\Option::get('uuopera', 'news_iblock_id', '1');
if ($iblockId <= 0) {
    fwrite(STDERR, "news_iblock_id not configured\n");
    exit(1);
}

echo "News iblock ID: $iblockId\n";

// WordPress categories → Bitrix section definitions
// slug => ['name' => display name, 'sort' => sort order]
$categories = [
    'oficialnaya-informaciya'     => ['name' => 'Официальная информация', 'sort' => 100],
    'news'                        => ['name' => 'Новости',                'sort' => 10],
];

// Ensure sections exist; collect slug → section ID map
$sectionIds = [];
$ibs = new CIBlockSection();
foreach ($categories as $slug => $def) {
    $res = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=CODE' => $slug, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'NAME']
    );
    if ($row = $res->Fetch()) {
        $sectionIds[$slug] = (int) $row['ID'];
        echo "Section exists: $slug (ID {$row['ID']})\n";
        continue;
    }
    $id = (int) $ibs->Add([
        'IBLOCK_ID' => $iblockId,
        'ACTIVE'    => 'Y',
        'NAME'      => $def['name'],
        'CODE'      => $slug,
        'SORT'      => $def['sort'],
    ]);
    if ($id > 0) {
        $sectionIds[$slug] = $id;
        echo "Created section: $slug (ID $id)\n";
    } else {
        global $APPLICATION;
        fwrite(STDERR, "Failed to create section $slug: " . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown') . "\n");
    }
}

// Map WP post XML_IDs to section slugs.
// Key = XML_ID stored in Bitrix (format: uuopera_wp_post_<wpId>)
// Value = slug of the section to assign
// These pairs come from wp_term_relationships in the WP dump.
$xmlIdToSection = [
    'uuopera_wp_post_18595' => 'oficialnaya-informaciya',  // Официальное заявление
    'uuopera_wp_post_22290' => 'oficialnaya-informaciya',  // Официальное опровержение
];

if (empty($xmlIdToSection)) {
    echo "No element→section assignments defined.\n";
} else {
    $ibe = new CIBlockElement();
    foreach ($xmlIdToSection as $xmlId => $slug) {
        if (!isset($sectionIds[$slug])) {
            echo "Skip $xmlId: section $slug not found\n";
            continue;
        }
        $secId = $sectionIds[$slug];
        $res = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, '=XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_SECTION_ID']
        );
        if (!($row = $res->Fetch())) {
            echo "Element not found: $xmlId\n";
            continue;
        }
        $eid = (int) $row['ID'];
        if ((int) $row['IBLOCK_SECTION_ID'] === $secId) {
            echo "Already in section: $xmlId (element $eid)\n";
            continue;
        }
        $ok = $ibe->Update($eid, ['IBLOCK_SECTION_ID' => $secId]);
        if ($ok) {
            echo "Assigned $xmlId (element $eid) → section $slug (ID $secId)\n";
        } else {
            global $APPLICATION;
            fwrite(STDERR, "Failed to update $eid: " . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown') . "\n");
        }
    }
}

echo "Done.\n";
