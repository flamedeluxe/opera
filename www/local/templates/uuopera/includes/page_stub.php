<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
$stubTitle = (string) ($GLOBALS['UUOPERA_STUB_TITLE'] ?? 'Раздел');
$stubLead = (string) ($GLOBALS['UUOPERA_STUB_LEAD'] ?? 'Страница будет подключена в Битрикс. Сейчас отображается заглушка.');
?>
<div class="wrapper-main wrapper-max py-24 md:py-32" data-header-color-schema="beige">
    <h1 class="text-h1"><?= htmlspecialchars($stubTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></h1>
    <p class="text-p2 mt-6 max-w-2xl"><?= htmlspecialchars($stubLead, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></p>
    <p class="mt-10"><a href="/" class="underline text-p2">На главную</a></p>
</div>
