<?php

declare(strict_types=1);

/**
 * Множественное свойство «Галерея» (тип файл): импорт с URL и вывод путей для шаблонов.
 */

/**
 * Скачивает URL во временные файлы и возвращает значение для SetPropertyValuesEx (ключи n0, n1, …).
 *
 * @param list<string>|array<int, string> $urls
 * @return array<string, array<string, mixed>>
 */
function uuopera_iblock_gallery_property_from_urls(array $urls): array
{
    $out = [];
    $i = 0;
    foreach ($urls as $u) {
        $u = trim((string) $u);
        if ($u === '') {
            continue;
        }
        $fa = CFile::MakeFileArray($u);
        if (!is_array($fa) || empty($fa['tmp_name']) || !is_file($fa['tmp_name'])) {
            continue;
        }
        $fa['MODULE_ID'] = 'iblock';
        $out['n' . $i] = $fa;
        $i++;
    }
    return $out;
}

/**
 * @return list<string> относительные пути (для src в шаблоне)
 */
function uuopera_iblock_gallery_paths_from_file_property(?array $prop): array
{
    if ($prop === null || ($prop['PROPERTY_TYPE'] ?? '') !== 'F') {
        return [];
    }
    $vals = $prop['VALUE'] ?? null;
    if ($vals === null || $vals === '' || $vals === false) {
        return [];
    }
    if (!is_array($vals)) {
        $vals = [$vals];
    }
    $gallery = [];
    foreach ($vals as $fid) {
        $fid = (int) $fid;
        if ($fid <= 0) {
            continue;
        }
        $path = CFile::GetPath($fid);
        if ($path !== false && $path !== '') {
            $gallery[] = (string) $path;
        }
    }
    return $gallery;
}
