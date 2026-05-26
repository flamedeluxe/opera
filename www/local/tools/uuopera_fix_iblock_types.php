<?php
declare(strict_types=1);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

// Move news iblock (ID=1) from type 'news' to type 'uuopera'
$ib = new CIBlock();
$ok = $ib->Update(1, ['IBLOCK_TYPE_ID' => 'uuopera']);
echo $ok ? "OK: iblock ID=1 type -> uuopera\n" : "Error: " . $ib->LAST_ERROR . "\n";

// Verify
$r = CIBlock::GetByID(1)->Fetch();
echo "iblock 1 type now: " . ($r['IBLOCK_TYPE_ID'] ?? '?') . "\n";
