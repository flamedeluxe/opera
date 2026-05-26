<?php

declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_services_seed.php
 *
 * Заполняет инфоблок «Платные услуги: вопросы» (uuopera_service_faq) данными из WP.
 * Добавляет новые свойства к инфоблоку (если ещё нет), затем создаёт элементы:
 *   - один элемент с ELEMENT_TYPE=intro (вступительный текст)
 *   - два элемента с ELEMENT_TYPE=file  (ссылки на PDF)
 *   - три элемента с ELEMENT_TYPE=service (услуги)
 *
 * Если элементы уже есть — пропускает создание (идемпотентно).
 */

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "Модуль iblock не подключён\n");
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_cms_iblocks_bootstrap.php';

// Убеждаемся, что инфоблок и все свойства созданы
$ids = uuopera_cms_bootstrap_iblocks();
$iblockId = $ids['service_faq'];
echo "iblock ID: $iblockId\n";

// Данные для заполнения
$intro = '<p>В нашем театре вы можете провести красивую фотосессию, арендовать сцену или холл для репетиций, взять в аренду высококлассное оборудование. Мы рады быть открытыми и полезными для наших гостей и партнеров</p>';

$files = [
    [
        'name'     => "Скачать положение\nо платных услугах",
        'file_url' => '/wp-content/uploads/2024/12/polozhenie-o-platnykh-aprel-2022.pdf',
    ],
    [
        'name'     => "Скачать прейскурант\nцен и услуг PDF",
        'file_url' => '/wp-content/uploads/2026/03/prejskurant-cen-1.pdf',
    ],
];

$services = [
    [
        'name'              => 'Аренда сцены, холла, здания',
        'description'       => '<p>Вы можете провести художественную фотосессию на территории нашего театра по предварительной договоренности. Вы можете связаться с нашим управляющим, чтобы обговорить детали.</p>',
        'contact_person'    => 'Смирнова Анжелика Анатольевна',
        'phone'             => '+7 (3012) 21-39-13',
        'email'             => '',
        'image_url'         => '/wp-content/uploads/2024/10/hall.png',
        'description_extra' => '',
    ],
    [
        'name'              => 'Фотосессии',
        'description'       => '<p>Вы можете провести художественную фотосессию на территории нашего театра по предварительной договоренности. Вы можете связаться с нашим управляющим, чтобы обговорить детали.</p>',
        'contact_person'    => 'Касса театра',
        'phone'             => '+7 (3012) 21-36-00',
        'email'             => '',
        'image_url'         => '/wp-content/uploads/2024/10/photoset-2.png',
        'description_extra' => '<p>Фотосессия в стенах театра надолго останется в памяти</p>',
    ],
    [
        'name'              => 'Материально-техническое оснащение для предоставляемых услуг',
        'description'       => '<p>Вы можете провести художественную фотосессию на территории нашего театра по предварительной договоренности. Вы можете связаться с нашим управляющим, чтобы обговорить детали.</p>',
        'contact_person'    => 'Шобогоров Александр Михайлович',
        'phone'             => '+7 (3012) 21-44-54',
        'email'             => '',
        'image_url'         => '/wp-content/uploads/2024/10/hall.png',
        'description_extra' => '',
    ],
];

// Проверяем, есть ли уже элементы
$existing = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId], false, ['nTopCount' => 1], ['ID']);
if ($existing->Fetch()) {
    echo "Элементы уже существуют — пропускаем. Удалите их вручную, если нужно пересоздать.\n";
    exit(0);
}

$el = new CIBlockElement();
$sort = 10;

// Intro element
$elId = $el->Add([
    'IBLOCK_ID'         => $iblockId,
    'ACTIVE'            => 'Y',
    'NAME'              => 'Вступительный текст',
    'SORT'              => $sort,
    'DETAIL_TEXT'       => $intro,
    'DETAIL_TEXT_TYPE'  => 'html',
    'PROPERTY_VALUES'   => [
        'ELEMENT_TYPE' => 'intro',
    ],
]);
if (!$elId) {
    global $APPLICATION;
    fwrite(STDERR, 'Ошибка создания intro: ' . ($APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown') . "\n");
    exit(1);
}
echo "Создан intro (ID $elId)\n";
$sort += 10;

// File elements
foreach ($files as $file) {
    $elId = $el->Add([
        'IBLOCK_ID'       => $iblockId,
        'ACTIVE'          => 'Y',
        'NAME'            => $file['name'],
        'SORT'            => $sort,
        'PROPERTY_VALUES' => [
            'ELEMENT_TYPE' => 'file',
            'FILE_URL'     => $file['file_url'],
        ],
    ]);
    if (!$elId) {
        fwrite(STDERR, 'Ошибка создания file: ' . $file['name'] . "\n");
    } else {
        echo "Создан file (ID $elId): {$file['name']}\n";
    }
    $sort += 10;
}

// Service elements
foreach ($services as $svc) {
    $props = [
        'ELEMENT_TYPE'      => 'service',
        'CONTACT_PERSON'    => $svc['contact_person'],
        'PHONE'             => $svc['phone'],
        'IMAGE_URL'         => $svc['image_url'],
    ];
    if ($svc['email'] !== '') {
        $props['EMAIL'] = $svc['email'];
    }
    if ($svc['description_extra'] !== '') {
        $props['DESCRIPTION_EXTRA'] = ['VALUE' => ['TEXT' => $svc['description_extra'], 'TYPE' => 'html']];
    }

    $elId = $el->Add([
        'IBLOCK_ID'         => $iblockId,
        'ACTIVE'            => 'Y',
        'NAME'              => $svc['name'],
        'SORT'              => $sort,
        'DETAIL_TEXT'       => $svc['description'],
        'DETAIL_TEXT_TYPE'  => 'html',
        'PROPERTY_VALUES'   => $props,
    ]);
    if (!$elId) {
        fwrite(STDERR, 'Ошибка создания service: ' . $svc['name'] . "\n");
    } else {
        echo "Создан service (ID $elId): {$svc['name']}\n";
    }
    $sort += 10;
}

echo "Готово.\n";
