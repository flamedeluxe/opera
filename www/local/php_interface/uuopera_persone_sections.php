<?php

declare(strict_types=1);

use Bitrix\Main\Loader;

function uuopera_persone_sections_definitions(): array
{
    return [
        'direction' => ['name' => 'Дирекция', 'sort' => 100],
        'hudr' => ['name' => 'Художественное руководство', 'sort' => 110],
        'opera' => ['name' => 'Оперная труппа', 'sort' => 120],
        'balet' => ['name' => 'Балетная труппа', 'sort' => 130],
        'orkestr' => ['name' => 'Оркестр', 'sort' => 140],
        'khor' => ['name' => 'Хор', 'sort' => 150],
        'khpch' => ['name' => 'Художественно-постановочная часть', 'sort' => 160],
        'ticketservice' => ['name' => 'Служба главного администратора', 'sort' => 170],
        'administration' => ['name' => 'Административный блок', 'sort' => 180],
        'khpch-hudr-6' => ['name' => 'Театрально-производственные мастерские', 'sort' => 190],
    ];
}

function uuopera_persone_group_order_map(): array
{
    return [
        'direction' => ['Директор', 'Заместители директора', 'Главный администратор', 'Начальник пресс-службы'],
        'hudr' => ['Художественное руководство', 'Художественный персонал'],
        'opera' => ['Заведующая оперной труппой', 'Педагог по вокалу', 'Сопрано', 'Меццо-сопрано', 'Тенора', 'Баритоны', 'Басы', 'Концертмейстеры'],
        'balet' => ['Главный балетмейстер', 'Заведующий балетной труппой', 'Балетмейстеры', 'Репетиторы', 'Концертмейстеры', 'Ведущий мастер сцены (женский состав)', 'Ведущий мастер сцены (мужской состав)', 'Артисты высшей категории (женский состав)', 'Артисты высшей категории (мужской состав)', 'Артист I-категории (женский состав)', 'Артист I-категории (мужской состав)', 'Артисты балета (мужской состав)', 'Артисты балета (женский состав)'],
        'orkestr' => ['Главный дирижер', 'Дирижер', 'Приглашенные дирижеры', 'Заведующий труппой оркестра', 'Группа струнных смычковых инструментов', 'Группа деревянных духовых инструментов', 'Группа медных духовых инструментов', 'Ударные инструменты'],
        'khor' => ['Главный хормейстер', 'Заведующая труппой хора', 'Хормейстер', 'Сопрано I', 'Сопрано II', 'Альт I', 'Альт II', 'Тенор I', 'Тенор II', 'Баритоны', 'Басы'],
        'khpch' => ['Заведующий художественно-постановочной частью', 'Заведующие цехами', 'Цех звукового сопровождения', 'Монтировочный цех', 'Костюмерный цех', 'Реквизитный цех', 'Гримерный цех', 'Цех светового сопровождения'],
        'ticketservice' => ['Главный администратор', 'Администраторы', 'Контролеры билетов'],
        'administration' => ['Планово-экономический отдел', 'Юридический отдел', 'Отдел кадров', 'Отдел продаж', 'Отдел рекламы и маркетинга', 'Отдел цифрового развития', 'Административно-хозяйственная часть', 'Билетная касса', 'Пресс-служба'],
        'khpch-hudr-6' => ['Заведующий театрально-производственными мастерскими', 'Заведующие цехами', 'Театрально-производственные мастерские'],
    ];
}

function uuopera_persone_ensure_section(string $code, string $name, int $sort): int
{
    if (!Loader::includeModule('iblock')) {
        return 0;
    }
    $iblockId = uuopera_persone_iblock_id();
    if ($iblockId <= 0) {
        return 0;
    }
    $code = strtolower((string) (preg_replace('/[^a-z0-9_-]/', '', $code) ?? ''));
    if ($code === '') {
        return 0;
    }
    $existing = uuopera_persone_section_id_by_code($code);
    if ($existing > 0) {
        (new CIBlockSection())->Update($existing, [
            'NAME' => $name,
            'SORT' => $sort,
            'ACTIVE' => 'Y',
        ]);

        return $existing;
    }
    $newId = (int) (new CIBlockSection())->Add([
        'IBLOCK_ID' => $iblockId,
        'NAME' => $name,
        'CODE' => $code,
        'SORT' => $sort,
        'ACTIVE' => 'Y',
    ]);

    return $newId;
}

function uuopera_persone_ensure_all_sections(): void
{
    foreach (uuopera_persone_sections_definitions() as $code => $def) {
        uuopera_persone_ensure_section((string) $code, (string) $def['name'], (int) $def['sort']);
    }
}

function uuopera_persone_section_id_by_code(string $code): int
{
    $code = trim($code);
    if ($code === '' || !Loader::includeModule('iblock')) {
        return 0;
    }
    $iblockId = uuopera_persone_iblock_id();
    if ($iblockId <= 0) {
        return 0;
    }
    $res = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID']
    );
    $row = $res ? $res->Fetch() : false;

    return is_array($row) ? (int) ($row['ID'] ?? 0) : 0;
}

function uuopera_persone_sections_catalog(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }
    $cache = [];
    if (!class_exists(Loader::class) || !Loader::includeModule('iblock')) {
        return $cache;
    }
    if (!function_exists('uuopera_persone_iblock_id')) {
        require_once __DIR__ . '/uuopera_cms_data.php';
    }
    $iblockId = uuopera_persone_iblock_id();
    if ($iblockId <= 0) {
        return $cache;
    }
    $res = CIBlockSection::GetList(
        ['SORT' => 'ASC', 'NAME' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID', 'NAME', 'CODE', 'SORT']
    );
    while ($row = $res->Fetch()) {
        $code = trim((string) ($row['CODE'] ?? ''));
        if ($code === '') {
            continue;
        }
        $cache[$code] = [
            'id' => (int) ($row['ID'] ?? 0),
            'name' => trim((string) ($row['NAME'] ?? '')),
            'sort' => (int) ($row['SORT'] ?? 500),
            'url' => '/personalii/' . rawurlencode($code) . '/',
        ];
    }

    return $cache;
}

function uuopera_persone_element_section_codes(int $elementId): array
{
    if ($elementId <= 0 || !Loader::includeModule('iblock')) {
        return [];
    }
    $codes = [];
    $res = CIBlockElement::GetElementGroups($elementId, true, ['ID', 'CODE']);
    while ($row = $res->Fetch()) {
        $code = trim((string) ($row['CODE'] ?? ''));
        if ($code !== '' && !in_array($code, $codes, true)) {
            $codes[] = $code;
        }
    }

    return $codes;
}

function uuopera_persone_assign_element_sections(int $elementId, array $sectionCodes): bool
{
    if ($elementId <= 0 || !Loader::includeModule('iblock')) {
        return false;
    }
    $iblockId = uuopera_persone_iblock_id();
    if ($iblockId <= 0) {
        return false;
    }
    $sectionIds = [];
    foreach ($sectionCodes as $code) {
        $sid = uuopera_persone_section_id_by_code((string) $code);
        if ($sid > 0 && !in_array($sid, $sectionIds, true)) {
            $sectionIds[] = $sid;
        }
    }
    if ($sectionIds === []) {
        return false;
    }
    CIBlockElement::SetElementSection($elementId, $sectionIds);
    (new CIBlockElement())->Update($elementId, ['IBLOCK_SECTION_ID' => $sectionIds[0]]);

    return true;
}

/**
 * @return list<array{0: string, 1: string}> [название, URL]
 */
function uuopera_persone_megamenu_link_items(): array
{
    $items = [];
    $catalog = uuopera_persone_sections_catalog();
    if ($catalog !== []) {
        uasort($catalog, static fn(array $a, array $b): int => ($a['sort'] <=> $b['sort']) ?: strcmp((string) $a['name'], (string) $b['name']));
        foreach ($catalog as $sec) {
            $name = trim((string) ($sec['name'] ?? ''));
            $url = trim((string) ($sec['url'] ?? ''));
            if ($name !== '' && $url !== '') {
                $items[] = [$name, $url];
            }
        }

        return $items;
    }
    foreach (uuopera_persone_sections_definitions() as $code => $def) {
        $items[] = [(string) $def['name'], '/personalii/' . rawurlencode((string) $code) . '/'];
    }

    return $items;
}

function uuopera_persone_category_codes_from_legacy_property(array $rawProps, int $elId): array
{
    $codes = [];
    $rawCat = $rawProps[$elId]['CATEGORY'] ?? null;
    if (is_array($rawCat) && isset($rawCat['VALUE'])) {
        foreach ((array) $rawCat['VALUE'] as $cv) {
            $cs = trim((string) $cv);
            if ($cs !== '' && !in_array($cs, $codes, true)) {
                $codes[] = $cs;
            }
        }
    } elseif (is_array($rawCat)) {
        foreach ($rawCat as $cv) {
            $cs = trim((string) $cv);
            if ($cs !== '' && !in_array($cs, $codes, true)) {
                $codes[] = $cs;
            }
        }
    }

    return $codes;
}
