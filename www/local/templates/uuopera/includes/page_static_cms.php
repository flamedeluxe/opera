<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
$schema       = (string) ($GLOBALS['UUOPERA_CMS_STATIC_HEADER_SCHEMA'] ?? 'beige');
$html         = (string) ($GLOBALS['UUOPERA_CMS_STATIC_HTML'] ?? '');
$wrapperClass = (string) ($GLOBALS['UUOPERA_CMS_STATIC_WRAPPER_CLASS'] ?? 'wrapper-main wrapper-max py-24 md:py-32 text-p2');
$schemaAttr   = (bool) ($GLOBALS['UUOPERA_CMS_STATIC_HEADER_SCHEMA_ATTR'] ?? true);
$staticId     = uuopera_cms_static_pages_iblock_id();
$elId         = (int) ($GLOBALS['UUOPERA_CMS_STATIC_ID'] ?? 0);
$editUrl      = ($elId > 0 && $staticId > 0)
    ? '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $staticId . '&type=uuopera&ID=' . $elId . '&lang=ru'
    : '';
?>
<div class="<?= htmlspecialchars($wrapperClass, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"<?= $schemaAttr ? ' data-header-color-schema="' . htmlspecialchars($schema, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"' : '' ?>>
    <?php if ($editUrl !== '' && isset($USER) && $USER->IsAdmin()): ?>
    <div style="margin-bottom:16px;">
        <a href="<?= htmlspecialcharsbx($editUrl) ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#f5f0e8;border:1px solid #c8b89a;border-radius:4px;font-size:12px;color:#5a4a3a;text-decoration:none;">
            ✏ Редактировать в админке (поле «Детальное описание»)
        </a>
        <a href="/bitrix/admin/iblock_list_admin.php?IBLOCK_ID=<?= (int) $staticId ?>&type=uuopera&lang=ru" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;margin-left:8px;padding:6px 12px;background:#f5f0e8;border:1px solid #c8b89a;border-radius:4px;font-size:12px;color:#5a4a3a;text-decoration:none;">
            Список статических страниц
        </a>
    </div>
    <?php endif; ?>
    <?= $html ?>
</div>
