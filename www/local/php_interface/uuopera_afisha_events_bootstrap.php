<?php

declare(strict_types=1);

/**
 * Инфоблок «События афиши» (uuopera / uuopera_afisha_events). Подключать после prolog.
 */

use Bitrix\Main\Config\Option;

/**
 * @return list<array<string, mixed>>
 */
function uuopera_afisha_events_property_definitions(): array
{
    return [
        [
            'NAME' => 'Категория URL (opera, ballet, …)',
            'ACTIVE' => 'Y',
            'SORT' => 50,
            'CODE' => 'CATEGORY',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 40,
        ],
        [
            'NAME' => 'Тип вёрстки',
            'ACTIVE' => 'Y',
            'SORT' => 55,
            'CODE' => 'LAYOUT',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 20,
            'HINT' => 'event — спектакль/концерт; excursion — экскурсия.',
        ],
        [
            'NAME' => 'Возрастной знак (6+)',
            'ACTIVE' => 'Y',
            'SORT' => 60,
            'CODE' => 'AGE',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 10,
        ],
        [
            'NAME' => 'Шапка: подзаголовок (HTML)',
            'ACTIVE' => 'Y',
            'SORT' => 120,
            'CODE' => 'HERO_META_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 8,
            'COL_COUNT' => 80,
            'HINT' => 'Блок под заголовком: абзацы цены, h1/h2 о спектакле и т.д.',
        ],
        [
            'NAME' => 'Radario: режим кнопки в шапке',
            'ACTIVE' => 'Y',
            'SORT' => 200,
            'CODE' => 'RADARIO_HERO_MODE',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 20,
            'HINT' => 'afisha — виджет Afisha; event — один сеанс Event; пусто — нет.',
        ],
        [
            'NAME' => 'Radario: ключ Afisha (шапка)',
            'ACTIVE' => 'Y',
            'SORT' => 210,
            'CODE' => 'RADARIO_AFISHA_KEY',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Radario: eventId кнопки в шапке',
            'ACTIVE' => 'Y',
            'SORT' => 220,
            'CODE' => 'RADARIO_HERO_EVENT_ID',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 20,
        ],
        [
            'NAME' => 'Сеансы (JSON)',
            'ACTIVE' => 'Y',
            'SORT' => 300,
            'CODE' => 'SESSIONS_JSON',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 16,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Состав (HTML)',
            'ACTIVE' => 'Y',
            'SORT' => 400,
            'CODE' => 'PARTICIPANTS_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 20,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Контент (HTML)',
            'ACTIVE' => 'Y',
            'SORT' => 410,
            'CODE' => 'CONTENT_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 20,
            'COL_COUNT' => 80,
            'HINT' => 'Блок постановочной группы и прочих текстовых блоков после описания.',
        ],
        [
            'NAME' => 'ID слайдера',
            'ACTIVE' => 'Y',
            'SORT' => 500,
            'CODE' => 'SLIDER_ID',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 24,
        ],
        [
            'NAME' => 'Галерея (файлы)',
            'ACTIVE' => 'Y',
            'SORT' => 505,
            'CODE' => 'GALLERY',
            'PROPERTY_TYPE' => 'F',
            'MULTIPLE' => 'Y',
            'FILE_TYPE' => 'jpg, jpeg, png, gif, webp',
        ],
        [
            'NAME' => 'Экскурсия: строка длительности в тексте',
            'ACTIVE' => 'Y',
            'SORT' => 600,
            'CODE' => 'FOOTER_DURATION',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Пушкинская карта (Y — показать значок)',
            'ACTIVE' => 'Y',
            'SORT' => 615,
            'CODE' => 'PUSHKIN_CARD',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 1,
        ],
        [
            'NAME' => 'Экскурсия: строка цены в тексте',
            'ACTIVE' => 'Y',
            'SORT' => 610,
            'CODE' => 'FOOTER_PRICE',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'URL источника (импорт)',
            'ACTIVE' => 'Y',
            'SORT' => 900,
            'CODE' => 'SOURCE_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 80,
        ],
    ];
}

/**
 * @param list<array<string, mixed>> $definitions
 */
function uuopera_afisha_events_ensure_properties(int $iblockId, array $definitions): void
{
    foreach ($definitions as $def) {
        $code = (string) $def['CODE'];
        $exists = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code])->Fetch();
        if ($exists) {
            continue;
        }
        $def['IBLOCK_ID'] = $iblockId;
        (new CIBlockProperty())->Add($def);
    }
}

/**
 * Скрывает устаревшие строковые поля обложки (главное фото — поле элемента «Анонс» / PREVIEW_PICTURE).
 */
function uuopera_afisha_events_deactivate_legacy_hero_props(int $iblockId): void
{
    if ($iblockId <= 0) {
        return;
    }
    foreach (['HERO_IMAGE', 'HERO_SRCSET'] as $code) {
        $res = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code]);
        if ($p = $res->Fetch()) {
            (new CIBlockProperty())->Update((int) $p['ID'], ['ACTIVE' => 'N']);
        }
    }
}

function uuopera_afisha_events_deactivate_legacy_gallery_url(int $iblockId): void
{
    if ($iblockId <= 0) {
        return;
    }
    $res = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'GALLERY_URL']);
    if ($p = $res->Fetch()) {
        (new CIBlockProperty())->Update((int) $p['ID'], ['ACTIVE' => 'N']);
    }
}

function uuopera_afisha_events_ensure_section(int $iblockId, string $sectionCode, string $sectionName, int $sort = 100): int
{
    $sectionCode = preg_replace('/[^a-z0-9_-]/i', '', $sectionCode) ?? '';
    if ($sectionCode === '') {
        $sectionCode = 'common';
    }
    $secRes = CIBlockSection::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $sectionCode, 'CHECK_PERMISSIONS' => 'N'], false, ['ID']);
    if ($row = $secRes->Fetch()) {
        return (int) $row['ID'];
    }
    $id = (int) (new CIBlockSection())->Add([
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'NAME' => $sectionName !== '' ? $sectionName : $sectionCode,
        'CODE' => $sectionCode,
        'SORT' => $sort,
    ]);
    return $id > 0 ? $id : 0;
}

function uuopera_afisha_events_seed_category_sections(int $iblockId): void
{
    if ($iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return;
    }
    $rows = [
        ['opera', 'Опера', 100],
        ['ballet', 'Балет', 200],
        ['concert', 'Концерты', 300],
        ['excursions', 'Экскурсии', 400],
        ['festivals', 'Фестивали', 500],
        ['online', 'Онлайн', 600],
        ['performances', 'Представления', 650],
        ['no-category', 'Без категории', 700],
        ['abonement', 'Абонемент', 750],
        ['musical', 'Мюзикл', 800],
    ];
    foreach ($rows as $r) {
        uuopera_afisha_events_ensure_section($iblockId, (string) $r[0], (string) $r[1], (int) $r[2]);
    }
}

/**
 * @return array{iblock_id: int}
 */
function uuopera_afisha_events_bootstrap_iblock(): array
{
    $siteRes = CSite::GetList($by = 'sort', $order = 'asc', ['ACTIVE' => 'Y']);
    $siteId = 's1';
    if ($s = $siteRes->Fetch()) {
        $siteId = (string) $s['LID'];
    }

    if (!CIBlockType::GetByID('uuopera')->Fetch()) {
        (new CIBlockType())->Add([
            'ID' => 'uuopera',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 600,
            'LANG' => [
                'ru' => [
                    'NAME' => 'uuopera.ru',
                    'SECTION_NAME' => 'Раздел',
                    'ELEMENT_NAME' => 'Событие',
                ],
            ],
        ]);
    }

    $row = CIBlock::GetList([], ['TYPE' => 'uuopera', 'CODE' => 'uuopera_afisha_events', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($row) {
        $bid = (int) $row['ID'];
    } else {
        $bid = (int) (new CIBlock())->Add([
            'ACTIVE' => 'Y',
            'NAME' => 'События афиши',
            'CODE' => 'uuopera_afisha_events',
            'IBLOCK_TYPE_ID' => 'uuopera',
            'LID' => [$siteId],
            'SORT' => 500,
            'GROUP_ID' => ['2' => 'R', '1' => 'X'],
            'INDEX_SECTION' => 'Y',
            'INDEX_ELEMENT' => 'Y',
            'SECTIONS' => 'Y',
            'SECTION_PAGE_URL' => '',
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'WORKFLOW' => 'N',
            'BIZPROC' => 'N',
        ]);
        if ($bid <= 0) {
            global $APPLICATION;
            throw new RuntimeException(
                'Ошибка создания инфоблока: ' . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown')
            );
        }
    }

    Option::set('uuopera', 'afisha_events_iblock_id', (string) $bid);
    uuopera_afisha_events_ensure_properties($bid, uuopera_afisha_events_property_definitions());
    uuopera_afisha_events_deactivate_legacy_hero_props($bid);
    uuopera_afisha_events_deactivate_legacy_gallery_url($bid);
    uuopera_afisha_events_seed_category_sections($bid);

    return ['iblock_id' => $bid];
}
