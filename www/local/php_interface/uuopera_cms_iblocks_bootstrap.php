<?php

declare(strict_types=1);

/**
 * Инфоблоки контента сайта (статические страницы, главная, проекты, «О театре», FAQ услуг).
 * Install: local/tools/uuopera_cms_iblocks_install.php
 */

use Bitrix\Main\Config\Option;

/**
 * @param list<array<string, mixed>> $definitions
 */
function uuopera_cms_ensure_properties(int $iblockId, array $definitions): void
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

function uuopera_cms_ensure_iblock(string $code, string $name, int $sort, array $propertyDefs): int
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

    $row = CIBlock::GetList([], ['TYPE' => 'uuopera', 'CODE' => $code, 'CHECK_PERMISSIONS' => 'N'])->Fetch();
    if ($row) {
        $bid = (int) $row['ID'];
    } else {
        $bid = (int) (new CIBlock())->Add([
            'ACTIVE' => 'Y',
            'NAME' => $name,
            'CODE' => $code,
            'IBLOCK_TYPE_ID' => 'uuopera',
            'LID' => [$siteId],
            'SORT' => $sort,
            'GROUP_ID' => ['2' => 'R', '1' => 'X'],
            'INDEX_SECTION' => 'N',
            'INDEX_ELEMENT' => 'Y',
            'SECTIONS' => 'N',
            'SECTION_PAGE_URL' => '',
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'WORKFLOW' => 'N',
            'BIZPROC' => 'N',
        ]);
        if ($bid <= 0) {
            global $APPLICATION;
            throw new RuntimeException(
                'Ошибка создания инфоблока ' . $code . ': ' . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown')
            );
        }
    }

    uuopera_cms_ensure_properties($bid, $propertyDefs);
    return $bid;
}

/**
 * @return array{
 *   static_pages: int,
 *   home_slides: int,
 *   projects: int,
 *   about: int,
 *   service_faq: int
 * }
 */
function uuopera_cms_bootstrap_iblocks(): array
{
    $staticProps = [
        [
            'NAME' => 'Путь URL (например /documents)',
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'CODE' => 'REQUEST_PATH',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 120,
            'IS_REQUIRED' => 'Y',
        ],
        [
            'NAME' => 'Схема шапки (beige|blue|transparent)',
            'ACTIVE' => 'Y',
            'SORT' => 110,
            'CODE' => 'HEADER_SCHEMA',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 30,
        ],
    ];

    $slideProps = [
        [
            'NAME' => 'Ссылка (куда ведёт слайд)',
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'CODE' => 'LINK_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
        [
            'NAME' => 'Подзаголовок / текст (HTML)',
            'ACTIVE' => 'Y',
            'SORT' => 110,
            'CODE' => 'SUBTEXT_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 12,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Возраст (12+)',
            'ACTIVE' => 'Y',
            'SORT' => 120,
            'CODE' => 'AGE_MARK',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 10,
        ],
        [
            'NAME' => 'Radario: ключ Afisha',
            'ACTIVE' => 'Y',
            'SORT' => 130,
            'CODE' => 'RADARIO_AFISHA_KEY',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Ссылка Intickets (data-intickets-url)',
            'ACTIVE' => 'Y',
            'SORT' => 140,
            'CODE' => 'INTICKETS_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
    ];

    $projectProps = [
        [
            'NAME' => 'Текст анонса (список)',
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'CODE' => 'TEASER_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 8,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'URL в списке (если не /projects/CODE/)',
            'ACTIVE' => 'Y',
            'SORT' => 110,
            'CODE' => 'LIST_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
    ];

    $aboutProps = [
        [
            'NAME' => 'Тип блока (timeline|mission|html)',
            'ACTIVE' => 'Y',
            'SORT' => 50,
            'CODE' => 'BLOCK_KIND',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 20,
        ],
        [
            'NAME' => 'Год (для timeline)',
            'ACTIVE' => 'Y',
            'SORT' => 60,
            'CODE' => 'YEAR_LABEL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 20,
        ],
        [
            'NAME' => 'Заголовок блока',
            'ACTIVE' => 'Y',
            'SORT' => 70,
            'CODE' => 'BLOCK_TITLE',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
        [
            'NAME' => 'Текст (HTML)',
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'CODE' => 'BODY_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 20,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Изображение сбоку',
            'ACTIVE' => 'Y',
            'SORT' => 90,
            'CODE' => 'SIDE_IMAGE',
            'PROPERTY_TYPE' => 'F',
            'MULTIPLE' => 'N',
        ],
        [
            'NAME' => 'Тема слайда (white|brown|blue)',
            'ACTIVE' => 'Y',
            'SORT' => 95,
            'CODE' => 'THEME',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 20,
        ],
        [
            'NAME' => 'Нижняя диаграмма (SVG/картинка)',
            'ACTIVE' => 'Y',
            'SORT' => 96,
            'CODE' => 'DIAGRAM_IMAGE',
            'PROPERTY_TYPE' => 'F',
            'MULTIPLE' => 'N',
        ],
    ];

    $faqProps = [
        [
            'NAME' => 'Ответ (HTML)',
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'CODE' => 'ANSWER_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 16,
            'COL_COUNT' => 80,
        ],
    ];

    $ids = [
        'static_pages' => uuopera_cms_ensure_iblock('uuopera_static_pages', 'Статические страницы', 430, $staticProps),
        'home_slides' => uuopera_cms_ensure_iblock('uuopera_home_slides', 'Главная: слайды', 440, $slideProps),
        'projects' => uuopera_cms_ensure_iblock('uuopera_projects', 'Проекты', 450, $projectProps),
        'about' => uuopera_cms_ensure_iblock('uuopera_about_blocks', 'О театре: блоки', 460, $aboutProps),
        'service_faq' => uuopera_cms_ensure_iblock('uuopera_service_faq', 'Платные услуги: вопросы', 470, $faqProps),
    ];

    Option::set('uuopera', 'cms_static_pages_iblock_id', (string) $ids['static_pages']);
    Option::set('uuopera', 'cms_home_slides_iblock_id', (string) $ids['home_slides']);
    Option::set('uuopera', 'cms_projects_iblock_id', (string) $ids['projects']);
    Option::set('uuopera', 'cms_about_iblock_id', (string) $ids['about']);
    Option::set('uuopera', 'cms_service_faq_iblock_id', (string) $ids['service_faq']);

    return $ids;
}
