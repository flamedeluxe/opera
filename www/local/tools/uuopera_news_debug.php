<?php
declare(strict_types=1);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

$iblockId = 1;
$res = CIBlockElement::GetList(
    ['ACTIVE_FROM' => 'DESC'],
    ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
    false,
    ['nTopCount' => 5],
    ['ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'PREVIEW_TEXT']
);

while ($row = $res->Fetch()) {
    $pic = $row['PREVIEW_PICTURE'];
    echo sprintf(
        "ID=%-5d PREVIEW_PICTURE=%-20s NAME=%s\n",
        $row['ID'],
        var_export($pic, true),
        mb_substr((string)$row['NAME'], 0, 50)
    );
    if (is_numeric($pic) && (int)$pic > 0) {
        $file = CFile::GetFileArray((int)$pic);
        echo "  -> SRC=" . ($file['SRC'] ?? 'null') . "\n";
    }
}
