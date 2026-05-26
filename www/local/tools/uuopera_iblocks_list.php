<?php
declare(strict_types=1);
// Выводит сводку всех инфоблоков и прописывает недостающие Options.
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

use \Bitrix\Main\Config\Option;

$r = CIBlock::GetList(['ID' => 'ASC'], ['CHECK_PERMISSIONS' => 'N']);
while ($ib = $r->Fetch()) {
    $id  = (int) $ib['ID'];
    $cnt = (int) CIBlockElement::GetList([], ['IBLOCK_ID' => $id, 'CHECK_PERMISSIONS' => 'N'], []);
    $gr  = [];
    foreach (CIBlock::GetGroupPermissions($id) as $gid => $perm) {
        $gr[] = "G$gid=$perm";
    }
    echo sprintf("ID=%-2d [%-8s] %-35s cnt=%-5d %s\n",
        $id, $ib['IBLOCK_TYPE_ID'], $ib['NAME'], $cnt, $gr ? implode(' ', $gr) : 'НЕТ ПРАВ');
}

echo "\n--- Options (до) ---\n";
$keys = [
    'news_iblock_id', 'afisha_events_iblock_id', 'megamenu_iblock_id',
    'cms_static_pages_iblock_id', 'cms_home_slides_iblock_id',
    'cms_projects_iblock_id', 'cms_about_iblock_id',
    'cms_service_faq_iblock_id', 'cms_contacts_iblock_id',
];
foreach ($keys as $k) {
    echo "  $k = " . Option::get('uuopera', $k, '') . "\n";
}

// Прописываем news_iblock_id явно если не задан
if (trim(Option::get('uuopera', 'news_iblock_id', '')) === '') {
    Option::set('uuopera', 'news_iblock_id', '1');
    echo "\n→ news_iblock_id задан = 1\n";
}
