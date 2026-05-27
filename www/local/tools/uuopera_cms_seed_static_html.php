<?php

declare(strict_types=1);

/**
 * CLI: php local/tools/uuopera_cms_seed_static_html.php [--dry-run] [--force]
 *
 * Writes production HTML snapshots into static CMS iblock elements
 * (editable in Bitrix admin under static pages iblock).
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

$argv = array_slice($argv, 1);
$dry = in_array('--dry-run', $argv, true);
$force = in_array('--force', $argv, true);

$staticIblockId = uuopera_cms_static_pages_iblock_id();
if ($staticIblockId <= 0) {
    fwrite(STDERR, "static pages iblock not configured\n");
    exit(1);
}

$paths = [
    '/documents' => '_cms_documents_body.html',
    '/brandbook' => '_cms_brandbook_body.html',
];

global $APPLICATION;
$errors = 0;

foreach ($paths as $requestPath => $includeFile) {
    $norm = uuopera_cms_normalize_request_path($requestPath);
    $file = $_SERVER['DOCUMENT_ROOT'] . '/local/templates/uuopera/includes/' . $includeFile;
    if (!is_file($file)) {
        fwrite(STDERR, "Missing snapshot: {$includeFile}\n");
        $errors++;
        continue;
    }
    $html = uuopera_html_decode_content((string) file_get_contents($file));
    if ($norm === '/documents') {
        $html = uuopera_html_prepare_documents_html($html);
    }

    $propRes = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        [
            'IBLOCK_ID' => $staticIblockId,
            'ACTIVE' => 'Y',
            'CHECK_PERMISSIONS' => 'N',
            'PROPERTY_REQUEST_PATH' => [$norm, $norm . '/'],
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'DETAIL_TEXT']
    );
    $row = $propRes ? $propRes->Fetch() : false;
    if (!is_array($row)) {
        fwrite(STDERR, "Element not found for {$norm}\n");
        $errors++;
        continue;
    }

    $elId = (int) ($row['ID'] ?? 0);
    $current = (string) ($row['DETAIL_TEXT'] ?? '');
    $needsSeed = $force
        || trim($current) === ''
        || str_contains($current, '[su_spoiler')
        || str_contains($current, '[_su_spoiler')
        || ($norm === '/documents' && !str_contains($current, 'class="docs"'));

    if (!$needsSeed) {
        echo "Skip {$norm} (element #{$elId} already has CMS HTML)\n";
        continue;
    }

    if ($dry) {
        echo "[dry] Update #{$elId} {$norm} (" . strlen($html) . " bytes)\n";
        continue;
    }

    $el = new CIBlockElement();
    $ok = $el->Update($elId, [
        'MODIFIED_BY' => 1,
        'DETAIL_TEXT' => $html,
        'DETAIL_TEXT_TYPE' => 'html',
    ]);
    if (!$ok) {
        $err = is_object($APPLICATION) && $APPLICATION->GetException()
            ? $APPLICATION->GetException()->GetString()
            : '?';
        fwrite(STDERR, "Update failed #{$elId} {$norm}: {$err}\n");
        $errors++;
        continue;
    }

    if (class_exists('\Bitrix\Main\Data\Cache')) {
        \Bitrix\Main\Data\Cache::clearCache(true);
    }

    echo "Updated #{$elId} {$norm}\n";
}

exit($errors > 0 ? 1 : 0);
