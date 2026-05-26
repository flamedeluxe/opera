<?php
declare(strict_types=1);

/**
 * CLI: php -f local/tools/uuopera_projects_seed.php
 * Заполняет инфоблок «Проекты» пятью элементами с продакшна.
 * Идемпотентно по CODE.
 */

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock не подключён\n");
    exit(1);
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_cms_iblocks_bootstrap.php';

$ids = uuopera_cms_bootstrap_iblocks();
$iblockId = $ids['projects'];
echo "iblock ID: $iblockId\n";

$projects = [
    [
        'code'  => 'opera100',
        'name'  => 'Конкурс на создание национальной оперы к 100-летию образования Республики Бурятия',
        'image' => '/wp-content/uploads/2024/07/yenkhye.jpg',
        'sort'  => 10,
    ],
    [
        'code'  => 'konkbalet100',
        'name'  => 'Конкурс на создание и постановку национального балета к 100-летию образования Республики Бурятия',
        'image' => '/wp-content/uploads/2024/07/bargudzhin-1024h666.jpg',
        'sort'  => 20,
    ],
    [
        'code'  => 's-16-po-19-aprelja-sostoitsja-vii-mezhdunarodnyj-konkurs-molodyh-opernyh-pevcov-imeni-narodnogo-artista-sssr-kima-bazarsadaeva',
        'name'  => 'С 16 по 19 апреля состоится VII международный конкурс молодых оперных певцов имени народного артиста СССР Кима Базарсадаева',
        'image' => '/wp-content/uploads/2025/01/kim-bazarsadaev-1024h666.jpg',
        'sort'  => 30,
    ],
    [
        'code'  => 'polozhenie-o-provedenii-proekta-ambassadory-pushkinskoj-karty',
        'name'  => 'Положение о проведении проекта «Амбассадоры Пушкинской карты»',
        'image' => '/wp-content/uploads/2025/02/ambassadory.jpg',
        'sort'  => 40,
    ],
    [
        'code'  => 'spasjom-zhizn-vmeste-vserossijskij-konkurs-socialnoj-reklamy-antinarkoticheskoj-napravlennosti-i-propagandy-zdorovogo-obraza-zhizni',
        'name'  => '«Спасём жизнь вместе!»: Всероссийский конкурс социальной рекламы антинаркотической направленности и пропаганды здорового образа жизни',
        'image' => '/wp-content/uploads/2025/12/konkurs_gunk_7-1920h1080.jpg',
        'sort'  => 50,
    ],
];

$el = new CIBlockElement();

foreach ($projects as $proj) {
    // Check if already exists
    $exists = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'CODE' => $proj['code']],
        false,
        ['nTopCount' => 1],
        ['ID']
    )->Fetch();

    if ($exists) {
        echo "Пропускаем (уже есть): {$proj['code']}\n";
        continue;
    }

    $fields = [
        'IBLOCK_ID'  => $iblockId,
        'ACTIVE'     => 'Y',
        'NAME'       => $proj['name'],
        'CODE'       => $proj['code'],
        'SORT'       => $proj['sort'],
        'DETAIL_TEXT'      => '',
        'DETAIL_TEXT_TYPE' => 'html',
    ];

    $imgPath = $_SERVER['DOCUMENT_ROOT'] . $proj['image'];
    if (is_file($imgPath)) {
        $fileArr = CFile::MakeFileArray($imgPath);
        if (is_array($fileArr)) {
            $fields['PREVIEW_PICTURE'] = $fileArr;
        }
    } else {
        echo "  WARN: файл не найден {$proj['image']}\n";
    }

    $eid = $el->Add($fields);
    if (!$eid) {
        global $APPLICATION;
        $err = $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'unknown';
        fwrite(STDERR, "Ошибка: {$proj['code']}: $err\n");
    } else {
        echo "Создан ID=$eid: {$proj['code']}\n";
    }
}

echo "Готово.\n";
