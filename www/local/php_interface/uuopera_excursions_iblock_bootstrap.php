<?php

declare(strict_types=1);

/**
 * Создание типа/инфоблока/раздела/свойств «Экскурсии». Подключать после prolog Битрикс.
 */

use Bitrix\Main\Config\Option;

/**
 * @return list<array<string, mixed>>
 */
function uuopera_excursions_get_property_definitions(): array
{
    return [
        [
            'NAME' => 'Цена (текст после «Цена билета:»)',
            'ACTIVE' => 'Y',
            'SORT' => 200,
            'CODE' => 'PRICE_VALUE',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 40,
            'HINT' => 'Например: 500 р.',
        ],
        [
            'NAME' => 'Длительность в шапке (после «Продолжительность:»)',
            'ACTIVE' => 'Y',
            'SORT' => 210,
            'CODE' => 'DURATION_HERO',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 40,
            'HINT' => 'Например: 45 минут',
        ],
        [
            'NAME' => 'Radario: ключ виджета Afisha',
            'ACTIVE' => 'Y',
            'SORT' => 300,
            'CODE' => 'RADARIO_AFISHA_KEY',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 80,
            'HINT' => 'Поле key из radario.Widgets.Afisha для кнопки «купить билет».',
        ],
        [
            'NAME' => 'Сеансы (JSON)',
            'ACTIVE' => 'Y',
            'SORT' => 400,
            'CODE' => 'SESSIONS_JSON',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 12,
            'COL_COUNT' => 80,
            'HINT' => 'Массив пар [ ["дата и время", eventId], ... ] (заполняется импортом).',
        ],
        [
            'NAME' => 'Галерея (файлы)',
            'ACTIVE' => 'Y',
            'SORT' => 500,
            'CODE' => 'GALLERY',
            'PROPERTY_TYPE' => 'F',
            'MULTIPLE' => 'Y',
            'FILE_TYPE' => 'jpg, jpeg, png, gif, webp',
        ],
        [
            'NAME' => 'ID слайдера (data-slider-default)',
            'ACTIVE' => 'Y',
            'SORT' => 510,
            'CODE' => 'SLIDER_ID',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 20,
            'HINT' => 'Уникальная строка для Swiper.',
        ],
        [
            'NAME' => 'Строка «Длительность» в тексте',
            'ACTIVE' => 'Y',
            'SORT' => 600,
            'CODE' => 'FOOTER_DURATION',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 60,
            'HINT' => 'Например: Длительность: около 45 мин.',
        ],
        [
            'NAME' => 'Строка «Цена» в тексте',
            'ACTIVE' => 'Y',
            'SORT' => 610,
            'CODE' => 'FOOTER_PRICE',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 60,
            'HINT' => 'Например: Цена билета: 500 р.',
        ],
    ];
}

/**
 * @param list<array<string, mixed>> $definitions
 */
function uuopera_excursions_install_ensure_properties(int $iblockId, array $definitions): void
{
    foreach ($definitions as $def) {
        $code = (string) $def['CODE'];
        $exists = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code])->Fetch();
        if ($exists) {
            continue;
        }
        $def['IBLOCK_ID'] = $iblockId;
        $id = (int) (new CIBlockProperty())->Add($def);
        if ($id <= 0) {
            global $APPLICATION;
            $err = $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown';
            echo "Не удалось создать свойство {$code}: {$err}\n";
        }
    }
}

/**
 * Главное фото — поле элемента «Анонс» (PREVIEW_PICTURE); старые строковые поля скрываем.
 */
function uuopera_excursions_deactivate_legacy_hero_props(int $iblockId): void
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

function uuopera_excursions_deactivate_legacy_gallery_url(int $iblockId): void
{
    if ($iblockId <= 0) {
        return;
    }
    $res = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'GALLERY_URL']);
    if ($p = $res->Fetch()) {
        (new CIBlockProperty())->Update((int) $p['ID'], ['ACTIVE' => 'N']);
    }
}

/**
 * @return array{iblock_id: int, section_id: int}
 */
function uuopera_excursions_bootstrap_structure(): array
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
                    'ELEMENT_NAME' => 'Элемент',
                ],
            ],
        ]);
    }

    $row = CIBlock::GetList([], ['TYPE' => 'uuopera', 'CODE' => 'uuopera_excursions', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($row) {
        $bid = (int) $row['ID'];
    } else {
        $bid = (int) (new CIBlock())->Add([
            'ACTIVE' => 'Y',
            'NAME' => 'Экскурсии (афиша)',
            'CODE' => 'uuopera_excursions',
            'IBLOCK_TYPE_ID' => 'uuopera',
            'LID' => [$siteId],
            'SORT' => 550,
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

    Option::set('uuopera', 'excursions_iblock_id', (string) $bid);

    uuopera_excursions_install_ensure_properties($bid, uuopera_excursions_get_property_definitions());
    uuopera_excursions_deactivate_legacy_hero_props($bid);
    uuopera_excursions_deactivate_legacy_gallery_url($bid);

    $sectionId = 0;
    $secRes = CIBlockSection::GetList([], ['IBLOCK_ID' => $bid, '=CODE' => 'excursions', 'CHECK_PERMISSIONS' => 'N'], false, ['ID']);
    if ($secRow = $secRes->Fetch()) {
        $sectionId = (int) $secRow['ID'];
    } else {
        $sectionId = (int) (new CIBlockSection())->Add([
            'IBLOCK_ID' => $bid,
            'ACTIVE' => 'Y',
            'NAME' => 'Экскурсии',
            'CODE' => 'excursions',
            'SORT' => 100,
        ]);
        if ($sectionId <= 0) {
            throw new RuntimeException('Не удалось создать раздел excursions.');
        }
    }

    return ['iblock_id' => $bid, 'section_id' => $sectionId];
}
