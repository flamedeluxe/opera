<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
$schema = (string) ($GLOBALS['UUOPERA_CMS_STATIC_HEADER_SCHEMA'] ?? 'beige');
$html = (string) ($GLOBALS['UUOPERA_CMS_STATIC_HTML'] ?? '');
?>
<div class="wrapper-main wrapper-max py-24 md:py-32 text-p2" data-header-color-schema="<?= htmlspecialchars($schema, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>">
    <?= $html ?>
</div>
