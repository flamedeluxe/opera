<?php

declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_persone_seed.php [--dry-run]
 * Импортирует персоналии из persone_data.json в инфоблок Персоналии.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module missing\n");
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/init.php';

$dry = in_array('--dry-run', $argv, true);

$iblockId = (int) \Bitrix\Main\Config\Option::get('uuopera', 'persone_iblock_id', '0');
if ($iblockId <= 0) {
    fwrite(STDERR, "persone_iblock_id=0, запустите uuopera_persone_iblock_install.php\n");
    exit(1);
}
echo "persone_iblock_id = $iblockId\n";

$jsonPath = __DIR__ . '/persone_data.json';
if (!is_file($jsonPath)) {
    fwrite(STDERR, "File not found: $jsonPath\n");
    exit(1);
}
$data = json_decode(file_get_contents($jsonPath), true);
if (!is_array($data)) {
    fwrite(STDERR, "JSON decode failed\n");
    exit(1);
}
echo "Loaded " . count($data) . " persone from JSON\n";

$ok = 0;
$skip = 0;
$err = 0;

foreach ($data as $item) {
    $wpId = (int) ($item['id'] ?? 0);
    $name = trim((string) ($item['name'] ?? ''));
    $slug = trim((string) ($item['slug'] ?? ''));
    $categories = (array) ($item['categories'] ?? []);
    $role = trim((string) ($item['role'] ?? ''));
    $photoUrl = trim((string) ($item['photo_url'] ?? ''));
    $sort = (int) ($item['sort'] ?? 500);
    $subCats = array_values(array_filter(array_map('trim', (array) ($item['sub_cats'] ?? []))));

    if ($name === '' || $slug === '') {
        $skip++;
        continue;
    }

    $xmlId = 'uuopera_wp_persone_' . $wpId;

    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID', 'CODE']
    );
    $existing = $res ? $res->Fetch() : false;
    $selfId = is_array($existing) ? (int) ($existing['ID'] ?? 0) : 0;

    if ($selfId > 0) {
        $code = (string) ($existing['CODE'] ?? '');
    } else {
        // Ensure unique code
        $code = $slug;
        $n = 0;
        while (true) {
            $chk = CIBlockElement::GetList(
                [], ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'],
                false, ['nTopCount' => 1], ['ID']
            );
            $row = $chk ? $chk->Fetch() : false;
            if (!is_array($row)) break;
            $n++;
            $code = $slug . '-u' . $n;
            if ($n > 9999) { $code = $slug . '-' . bin2hex(random_bytes(3)); break; }
        }
    }

    $fields = [
        'MODIFIED_BY' => 1,
        'IBLOCK_SECTION_ID' => false,
        'IBLOCK_ID' => $iblockId,
        'NAME' => $name,
        'CODE' => $code,
        'XML_ID' => $xmlId,
        'ACTIVE' => 'Y',
        'SORT' => $sort,
        'PREVIEW_TEXT' => '',
        'PREVIEW_TEXT_TYPE' => 'text',
        'DETAIL_TEXT' => '',
        'DETAIL_TEXT_TYPE' => 'html',
    ];

    // Attempt photo
    if ($photoUrl !== '' && !$dry) {
        $fa = CFile::MakeFileArray($photoUrl);
        if (is_array($fa) && empty($fa['error']) && !empty($fa['tmp_name']) && is_file((string) $fa['tmp_name'])) {
            $fa['MODULE_ID'] = 'iblock';
            $fields['PREVIEW_PICTURE'] = $fa;
            $fields['DETAIL_PICTURE'] = $fa;
        }
    }

    $props = [
        'ROLE' => $role,
        'PHOTO_URL' => $photoUrl,
    ];
    if ($categories !== []) {
        $props['CATEGORY'] = $categories;
    }
    if ($subCats !== []) {
        $props['GROUPS'] = $subCats;
    }

    if ($dry) {
        echo "DRY: $xmlId $name cats=" . implode(',', $categories) . "\n";
        $ok++;
        continue;
    }

    global $APPLICATION;
    $el = new CIBlockElement();
    if ($selfId > 0) {
        $result = $el->Update($selfId, $fields);
        if (!$result) {
            $msg = ($APPLICATION && $APPLICATION->GetException()) ? $APPLICATION->GetException()->GetString() : '?';
            fwrite(STDERR, "Update $selfId $xmlId: $msg\n");
            $err++;
            continue;
        }
        CIBlockElement::SetPropertyValuesEx($selfId, $iblockId, $props);
        $ok++;
    } else {
        $newId = (int) $el->Add($fields);
        if ($newId <= 0) {
            $msg = ($APPLICATION && $APPLICATION->GetException()) ? $APPLICATION->GetException()->GetString() : '?';
            fwrite(STDERR, "Add $xmlId: $msg\n");
            $err++;
            continue;
        }
        CIBlockElement::SetPropertyValuesEx($newId, $iblockId, $props);
        $ok++;
    }
}

echo "OK=$ok SKIP=$skip ERR=$err" . ($dry ? ' (dry-run)' : '') . "\n";
