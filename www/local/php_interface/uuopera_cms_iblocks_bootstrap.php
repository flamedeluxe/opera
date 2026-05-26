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

function uuopera_cms_ensure_iblock(string $code, string $name, int $sort, array $propertyDefs, bool $useSections = false): int
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
        if ($useSections) {
            (new CIBlock())->Update($bid, [
                'SECTIONS' => 'Y',
                'INDEX_SECTION' => 'Y',
            ]);
        }
    } else {
        $bid = (int) (new CIBlock())->Add([
            'ACTIVE' => 'Y',
            'NAME' => $name,
            'CODE' => $code,
            'IBLOCK_TYPE_ID' => 'uuopera',
            'LID' => [$siteId],
            'SORT' => $sort,
            'GROUP_ID' => ['2' => 'R', '1' => 'X'],
            'INDEX_SECTION' => $useSections ? 'Y' : 'N',
            'INDEX_ELEMENT' => 'Y',
            'SECTIONS' => $useSections ? 'Y' : 'N',
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

function uuopera_cms_ensure_iblock_section(int $iblockId, string $sectionCode, string $sectionName, int $sort): int
{
    if ($iblockId <= 0 || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return 0;
    }
    $sectionCode = strtolower((string) (preg_replace('/[^a-z0-9_-]/', '', $sectionCode) ?? ''));
    if ($sectionCode === '') {
        return 0;
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

/**
 * @param array{
 *   contacts?: int,
 *   static_pages: int,
 *   home_slides: int,
 *   projects: int,
 *   about: int,
 *   service_faq: int
 * } $ids
 */
function uuopera_cms_seed_iblock_admin_sections(array $ids): void
{
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return;
    }
    $sp = (int) ($ids['static_pages'] ?? 0);
    if ($sp > 0) {
        uuopera_cms_ensure_iblock_section($sp, 'common', 'Общие страницы', 100);
        uuopera_cms_ensure_iblock_section($sp, 'theatre', 'Театр', 200);
        uuopera_cms_ensure_iblock_section($sp, 'visitors', 'Посетителям', 300);
        uuopera_cms_ensure_iblock_section($sp, 'legal', 'Юридические страницы', 400);
    }
    $hs = (int) ($ids['home_slides'] ?? 0);
    if ($hs > 0) {
        uuopera_cms_ensure_iblock_section($hs, 'main_slider', 'Слайды главной', 100);
    }
    $pr = (int) ($ids['projects'] ?? 0);
    if ($pr > 0) {
        uuopera_cms_ensure_iblock_section($pr, 'current', 'Текущие', 100);
        uuopera_cms_ensure_iblock_section($pr, 'archive', 'Архив', 200);
    }
    $ab = (int) ($ids['about'] ?? 0);
    if ($ab > 0) {
        uuopera_cms_ensure_iblock_section($ab, 'mission', 'Миссия и ценности', 100);
        uuopera_cms_ensure_iblock_section($ab, 'history', 'История', 200);
        uuopera_cms_ensure_iblock_section($ab, 'management', 'Руководство', 300);
        uuopera_cms_ensure_iblock_section($ab, 'other', 'Прочее', 900);
    }
    $fq = (int) ($ids['service_faq'] ?? 0);
    if ($fq > 0) {
        uuopera_cms_ensure_iblock_section($fq, 'intro', 'Вводные блоки', 100);
        uuopera_cms_ensure_iblock_section($fq, 'questions', 'Вопросы', 200);
        uuopera_cms_ensure_iblock_section($fq, 'files', 'Файлы и реквизиты', 300);
    }
    $ct = (int) ($ids['contacts'] ?? 0);
    if ($ct > 0) {
        uuopera_cms_ensure_iblock_section($ct, 'main', 'Контент страницы', 100);
    }
}

/**
 * @return array{
 *   contacts: int,
 *   static_pages: int,
 *   home_slides: int,
 *   projects: int,
 *   about: int,
 *   service_faq: int
 * }
 */
function uuopera_cms_bootstrap_iblocks(): array
{
    $contactsProps = [
        [
            'NAME' => 'Адрес (HTML, колонка слева)',
            'ACTIVE' => 'Y',
            'SORT' => 100,
            'CODE' => 'ADDRESS_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 12,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Телефоны и email (HTML, сетка справа)',
            'ACTIVE' => 'Y',
            'SORT' => 110,
            'CODE' => 'GRID_HTML',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 24,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Карта: координаты (широта, долгота)',
            'ACTIVE' => 'Y',
            'SORT' => 120,
            'CODE' => 'MAP_LATLNG',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Карта: API-ключ Яндекс.Карт',
            'ACTIVE' => 'Y',
            'SORT' => 130,
            'CODE' => 'MAP_API_KEY',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'Форма: URL отправки (action)',
            'ACTIVE' => 'Y',
            'SORT' => 140,
            'CODE' => 'FORM_ACTION',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
        [
            'NAME' => 'Блок «обратная связь»: картинка (файл)',
            'ACTIVE' => 'Y',
            'SORT' => 150,
            'CODE' => 'FEEDBACK_IMAGE',
            'PROPERTY_TYPE' => 'F',
            'MULTIPLE' => 'N',
            'FILE_TYPE' => 'jpg, jpeg, png, gif, webp',
        ],
        [
            'NAME' => 'Блок «обратная связь»: URL картинки (если без файла)',
            'ACTIVE' => 'Y',
            'SORT' => 160,
            'CODE' => 'FEEDBACK_IMAGE_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 500,
        ],
    ];

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
        [
            'NAME' => 'Видео (MP4, горизонтальное)',
            'ACTIVE' => 'Y',
            'SORT' => 150,
            'CODE' => 'VIDEO_MP4',
            'PROPERTY_TYPE' => 'F',
            'FILE_TYPE' => 'mp4,webm',
        ],
        [
            'NAME' => 'Видео (MP4, вертикальное — для мобильных)',
            'ACTIVE' => 'Y',
            'SORT' => 160,
            'CODE' => 'VIDEO_MP4_PORTRAIT',
            'PROPERTY_TYPE' => 'F',
            'FILE_TYPE' => 'mp4,webm',
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
        [
            'NAME' => 'URL изображения сбоку (запасной)',
            'ACTIVE' => 'Y',
            'SORT' => 91,
            'CODE' => 'SIDE_IMAGE_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 500,
        ],
        [
            'NAME' => 'Подпись к изображению сбоку',
            'ACTIVE' => 'Y',
            'SORT' => 92,
            'CODE' => 'SIDE_CAPTION',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
        [
            'NAME' => 'URL диаграммы (запасной)',
            'ACTIVE' => 'Y',
            'SORT' => 97,
            'CODE' => 'DIAGRAM_IMAGE_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 500,
        ],
    ];

    $faqProps = [
        [
            'NAME' => 'Тип элемента (service | intro | file)',
            'ACTIVE' => 'Y',
            'SORT' => 10,
            'CODE' => 'ELEMENT_TYPE',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 20,
        ],
        [
            'NAME' => 'Контактное лицо',
            'ACTIVE' => 'Y',
            'SORT' => 20,
            'CODE' => 'CONTACT_PERSON',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
        [
            'NAME' => 'Телефон',
            'ACTIVE' => 'Y',
            'SORT' => 30,
            'CODE' => 'PHONE',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 60,
        ],
        [
            'NAME' => 'Email',
            'ACTIVE' => 'Y',
            'SORT' => 40,
            'CODE' => 'EMAIL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 100,
        ],
        [
            'NAME' => 'URL изображения',
            'ACTIVE' => 'Y',
            'SORT' => 50,
            'CODE' => 'IMAGE_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
        [
            'NAME' => 'Дополнительное описание (HTML)',
            'ACTIVE' => 'Y',
            'SORT' => 60,
            'CODE' => 'DESCRIPTION_EXTRA',
            'PROPERTY_TYPE' => 'T',
            'ROW_COUNT' => 10,
            'COL_COUNT' => 80,
        ],
        [
            'NAME' => 'URL файла (для type=file)',
            'ACTIVE' => 'Y',
            'SORT' => 70,
            'CODE' => 'FILE_URL',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => 1,
            'COL_COUNT' => 200,
        ],
    ];

    $ids = [
        'contacts' => uuopera_cms_ensure_iblock('uuopera_contacts_settings', 'Страница «Контакты»', 426, $contactsProps, true),
        'static_pages' => uuopera_cms_ensure_iblock('uuopera_static_pages', 'Статические страницы', 430, $staticProps, true),
        'home_slides' => uuopera_cms_ensure_iblock('uuopera_home_slides', 'Главная: слайды', 440, $slideProps, true),
        'projects' => uuopera_cms_ensure_iblock('uuopera_projects', 'Проекты', 450, $projectProps, true),
        'about' => uuopera_cms_ensure_iblock('uuopera_about_blocks', 'О театре: блоки', 460, $aboutProps, true),
        'service_faq' => uuopera_cms_ensure_iblock('uuopera_service_faq', 'Платные услуги', 470, $faqProps, true),
    ];

    uuopera_cms_seed_iblock_admin_sections($ids);

    Option::set('uuopera', 'cms_contacts_iblock_id', (string) $ids['contacts']);
    Option::set('uuopera', 'cms_static_pages_iblock_id', (string) $ids['static_pages']);
    Option::set('uuopera', 'cms_home_slides_iblock_id', (string) $ids['home_slides']);
    Option::set('uuopera', 'cms_projects_iblock_id', (string) $ids['projects']);
    Option::set('uuopera', 'cms_about_iblock_id', (string) $ids['about']);
    Option::set('uuopera', 'cms_service_faq_iblock_id', (string) $ids['service_faq']);

    return $ids;
}
