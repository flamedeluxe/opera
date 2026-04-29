<?php

/**
 * Однократная установка инфоблока «Мегаменю» (колонки = разделы, пункты = элементы + свойство LINK).
 *
 * Запуск (в контейнере PHP или на сервере):
 *   php local/tools/uuopera_megamenu_iblock_install.php
 *
 * Либо из браузера под администратором:
 *   https://сайт/local/tools/uuopera_megamenu_iblock_install.php
 */

declare(strict_types=1);

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
    global $USER;
    if (!is_object($USER) || !$USER->IsAdmin()) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Только для администратора.';
        exit;
    }
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    echo "Модуль iblock не установлен.\n";
    exit(1);
}

use Bitrix\Main\Config\Option;

$siteRes = CSite::GetList($by = 'sort', $order = 'asc', ['ACTIVE' => 'Y']);
$siteId = 's1';
if ($s = $siteRes->Fetch()) {
    $siteId = (string) $s['LID'];
}

$exists = CIBlock::GetList([], ['TYPE' => 'uuopera', 'CODE' => 'uuopera_megamenu', 'CHECK_PERMISSIONS' => 'N'])->Fetch();
if ($exists) {
    $bid = (int) $exists['ID'];
    Option::set('uuopera', 'megamenu_iblock_id', (string) $bid);
    echo "Инфоблок уже есть (ID={$bid}). Опция uuopera/megamenu_iblock_id обновлена.\n";
    exit(0);
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
                'SECTION_NAME' => 'Колонка меню',
                'ELEMENT_NAME' => 'Пункт меню',
            ],
        ],
    ]);
}

$bid = (int) (new CIBlock())->Add([
    'ACTIVE' => 'Y',
    'NAME' => 'Мегаменю шапки',
    'CODE' => 'uuopera_megamenu',
    'IBLOCK_TYPE_ID' => 'uuopera',
    'LID' => [$siteId],
    'SORT' => 600,
    'GROUP_ID' => ['2' => 'R', '1' => 'X'],
    'INDEX_SECTION' => 'Y',
    'INDEX_ELEMENT' => 'N',
    'SECTIONS' => 'Y',
    'SECTION_PAGE_URL' => '',
    'LIST_PAGE_URL' => '',
    'DETAIL_PAGE_URL' => '',
    'WORKFLOW' => 'N',
    'BIZPROC' => 'N',
]);

if ($bid <= 0) {
    global $APPLICATION;
    echo 'Ошибка создания инфоблока: ' . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown') . "\n";
    exit(1);
}

$propId = (new CIBlockProperty())->Add([
    'IBLOCK_ID' => $bid,
    'NAME' => 'Ссылка',
    'ACTIVE' => 'Y',
    'SORT' => 100,
    'CODE' => 'LINK',
    'PROPERTY_TYPE' => 'S',
    'ROW_COUNT' => 1,
    'COL_COUNT' => 60,
]);

if (!$propId) {
    echo "Не удалось создать свойство LINK.\n";
    exit(1);
}

$imageUrls = [
    'uu_m_afisha' => 'https://uuopera.ru/wp-content/uploads/2024/12/1-potolok.jpg',
    'uu_m_teatre' => 'https://uuopera.ru/wp-content/uploads/2024/12/2-potolok.jpg',
    'uu_m_projects' => 'https://uuopera.ru/wp-content/uploads/2024/12/3-potolok.jpg',
    'uu_m_persons' => 'https://uuopera.ru/wp-content/uploads/2024/12/4-potolok.jpg',
];

$menuTree = [
    ['xml' => 'uu_m_afisha', 'name' => 'Афиша', 'sort' => 100, 'img_key' => 'uu_m_afisha', 'items' => [
        ['Все события', '/afisha/'],
        ['Представления', '/afisha/performances/'],
        ['Фестивали', '/afisha/festivals/'],
        ['Экскурсии', '/afisha/excursions/'],
        ['Онлайн-спектакли', '/afisha/online/'],
    ]],
    ['xml' => 'uu_m_teatre', 'name' => 'Театр', 'sort' => 200, 'img_key' => 'uu_m_teatre', 'items' => [
        ['О театре', '/missiya-i-cennosti/'],
        ['Платные услуги', '/services/'],
        ['Документы', '/documents/'],
        ['Брендбук', '/brandbook/'],
        ['Официальная информация', '/category/oficialnaya-informaciya/'],
    ]],
    ['xml' => 'uu_m_projects', 'name' => 'Проекты', 'sort' => 300, 'img_key' => 'uu_m_projects', 'items' => [
        ['Все проекты', '/projects/'],
        ['Национальная опера', '/projects/opera100/'],
        ['Национальный балет', '/projects/konkbalet100/'],
    ]],
    ['xml' => 'uu_m_persons', 'name' => 'Персоны', 'sort' => 400, 'img_key' => 'uu_m_persons', 'items' => [
        ['Художественное руководство', '/personalii/hudr/'],
        ['Опера', '/personalii/opera/'],
        ['Балет', '/personalii/balet/'],
        ['Оркестр', '/personalii/orkestr/'],
        ['Хор', '/personalii/khor/'],
    ]],
    ['xml' => 'uu_m_visitors', 'name' => 'Посетителям', 'sort' => 500, 'img_key' => null, 'items' => [
        ['Театральный этикет', '/for-visitors/etiquette/'],
        ['Возврат билетов', '/for-visitors/ticket-refund/'],
        ['Льготные билеты', '/for-visitors/discounted-tickets/'],
        ['Доступная среда', '/for-visitors/dostupnaya-sreda/'],
    ]],
];

$bs = new CIBlockSection();
$el = new CIBlockElement();

foreach ($menuTree as $col) {
    $fields = [
        'IBLOCK_ID' => $bid,
        'ACTIVE' => 'Y',
        'NAME' => $col['name'],
        'SORT' => $col['sort'],
        'XML_ID' => $col['xml'],
    ];
    if (!empty($col['img_key']) && isset($imageUrls[$col['img_key']])) {
        $file = @CFile::MakeFileArray($imageUrls[$col['img_key']]);
        if (is_array($file) && !empty($file['tmp_name'])) {
            $fields['PICTURE'] = $file;
        }
    }
    $sid = (int) $bs->Add($fields);
    if ($sid <= 0) {
        echo "Ошибка раздела {$col['name']}\n";
        continue;
    }
    $es = 100;
    foreach ($col['items'] as [$title, $url]) {
        $el->Add([
            'IBLOCK_ID' => $bid,
            'IBLOCK_SECTION_ID' => $sid,
            'NAME' => $title,
            'ACTIVE' => 'Y',
            'SORT' => $es,
            'PROPERTY_VALUES' => ['LINK' => $url],
        ]);
        $es += 100;
    }
}

Option::set('uuopera', 'megamenu_iblock_id', (string) $bid);

echo "Готово. Инфоблок ID={$bid}, тип uuopera, код uuopera_megamenu.\n";
echo "Админка: Контент → Информационные блоки → uuopera.ru → Мегаменю шапки.\n";
echo "Очистите кеш сайта.\n";
