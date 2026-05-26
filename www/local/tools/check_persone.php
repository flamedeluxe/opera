<?php
$_SERVER['DOCUMENT_ROOT'] = '/var/www/bitrix';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require '/var/www/bitrix/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');
$id = (int)\Bitrix\Main\Config\Option::get('uuopera', 'persone_iblock_id', '0');
echo "iblock_id=$id\n";
$res = CIBlockElement::GetList([], ['IBLOCK_ID' => $id, 'CHECK_PERMISSIONS' => 'N'], false, false, ['ID']);
$cnt = 0;
while ($res->Fetch()) $cnt++;
echo "Count: $cnt\n";
