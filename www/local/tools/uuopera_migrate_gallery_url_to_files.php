<?php

declare(strict_types=1);

/**
 * Одноразовая миграция: множественное свойство GALLERY_URL (строки-URL донора)
 * → GALLERY (тип «файл», множественное) через CFile::MakeFileArray.
 *
 * CLI:
 *   php -f local/tools/uuopera_migrate_gallery_url_to_files.php
 *   php -f local/tools/uuopera_migrate_gallery_url_to_files.php --force
 *   php -f local/tools/uuopera_migrate_gallery_url_to_files.php --only=afisha
 *   php -f local/tools/uuopera_migrate_gallery_url_to_files.php --only=excursions
 *
 * По умолчанию обрабатываются только элементы, у которых ещё нет файлов в GALLERY.
 * --force пересобирает GALLERY из GALLERY_URL (старые файлы свойства заменяются).
 */

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
    header('HTTP/1.1 403 Forbidden');
    echo 'Только CLI.';
    exit;
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "Модуль iblock не установлен.\n");
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_iblock_gallery.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_excursions.php';

$force = in_array('--force', $argv, true);
$only = 'all';
foreach ($argv as $arg) {
    if (preg_match('/^--only=(afisha|excursions|all)$/', $arg, $m)) {
        $only = $m[1];
    }
}

/**
 * @return list<string>
 */
function uuopera_migrate_gallery_url_list_from_prop(array $prop): array
{
    $v = $prop['VALUE'] ?? null;
    if ($v === null || $v === '' || $v === false) {
        return [];
    }
    if (!is_array($v)) {
        $v = [$v];
    }
    $out = [];
    foreach ($v as $x) {
        $x = trim((string) $x);
        if ($x !== '') {
            $out[] = $x;
        }
    }
    return $out;
}

function uuopera_migrate_gallery_for_iblock(int $iblockId, string $label, bool $force): int
{
    if ($iblockId <= 0) {
        echo "{$label}: инфоблок не задан (опция uuopera), пропуск.\n";
        return 0;
    }

    $res = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME']
    );

    $updated = 0;
    while ($ob = $res->GetNextElement()) {
        $fields = $ob->GetFields();
        $id = (int) ($fields['ID'] ?? 0);
        $props = $ob->GetProperties([], ['GALLERY', 'GALLERY_URL']);
        $urls = uuopera_migrate_gallery_url_list_from_prop($props['GALLERY_URL'] ?? []);
        if ($urls === []) {
            continue;
        }

        $galProp = $props['GALLERY'] ?? [];
        $existingCount = 0;
        if (($galProp['PROPERTY_TYPE'] ?? '') === 'F') {
            $existingCount = count(uuopera_iblock_gallery_paths_from_file_property($galProp));
        }

        if (!$force && $existingCount > 0) {
            continue;
        }

        $fileMap = uuopera_iblock_gallery_property_from_urls($urls);
        if ($fileMap === []) {
            fwrite(STDERR, "{$label} ID={$id}: не удалось скачать ни одного файла по URL.\n");
            continue;
        }

        CIBlockElement::SetPropertyValuesEx($id, $iblockId, [
            'GALLERY' => $fileMap,
            'GALLERY_URL' => false,
        ]);
        $updated++;
        echo "{$label} ID={$id} «" . ($fields['NAME'] ?? '') . "»: загружено файлов: " . count($fileMap) . "\n";
    }

    return $updated;
}

$total = 0;
if ($only === 'all' || $only === 'afisha') {
    $total += uuopera_migrate_gallery_for_iblock(uuopera_afisha_events_iblock_id(), 'Афиша', $force);
}
if ($only === 'all' || $only === 'excursions') {
    $total += uuopera_migrate_gallery_for_iblock(uuopera_excursions_iblock_id(), 'Экскурсии', $force);
}

echo "Готово. Обновлено элементов: {$total}\n";
