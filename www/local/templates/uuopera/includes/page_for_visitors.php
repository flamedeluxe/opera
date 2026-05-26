<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */

$staticId = uuopera_cms_static_pages_iblock_id();

// Current path (strip trailing slash)
$currentPath = rtrim((string) ($GLOBALS['UUOPERA_FOR_VISITORS_PATH'] ?? '/for-visitors'), '/');

// Load parent page intro (/for-visitors) and build tabs dynamically from iblock
$introHtml = '';
$tabs = []; // [path => title]

if ($staticId > 0 && \Bitrix\Main\Loader::includeModule('iblock')) {
    $parentPage = uuopera_cms_static_page_find('/for-visitors');
    if ($parentPage !== null) {
        $introHtml = $parentPage['html'];
    }

    // Collect all sub-pages under /for-visitors/ ordered by SORT
    $tabRes = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        [
            'IBLOCK_ID'         => $staticId,
            'ACTIVE'            => 'Y',
            'CHECK_PERMISSIONS' => 'N',
        ],
        false,
        false,
        ['ID', 'NAME', 'PROPERTY_REQUEST_PATH', 'SORT']
    );
    while ($tabRow = $tabRes->Fetch()) {
        $tabPath = rtrim((string) ($tabRow['PROPERTY_REQUEST_PATH_VALUE'] ?? ''), '/');
        if ($tabPath !== '/for-visitors' && str_starts_with($tabPath, '/for-visitors/')) {
            $tabs[$tabPath] = (string) $tabRow['NAME'];
        }
    }
}

// Load current sub-page content
$contentHtml  = '';
$pageTitle    = 'Посетителям театра';
$editUrl      = '';

if ($currentPath !== '/for-visitors' && $staticId > 0 && \Bitrix\Main\Loader::includeModule('iblock')) {
    $subPage = uuopera_cms_static_page_find($currentPath);
    if ($subPage !== null) {
        $contentHtml = $subPage['html'];
        $pageTitle   = $subPage['title'];
        $APPLICATION->SetTitle($pageTitle . ' - Бурятский театр оперы и балета');
        if (!empty($subPage['id'])) {
            $editUrl = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $staticId
                . '&type=uuopera&ID=' . $subPage['id'] . '&lang=ru';
        }
    }
} elseif ($currentPath === '/for-visitors') {
    $contentHtml = $introHtml;
    $introHtml   = '';
    if ($parentPage !== null && !empty($parentPage['id'])) {
        $editUrl = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . $staticId
            . '&type=uuopera&ID=' . $parentPage['id'] . '&lang=ru';
    }
}
?>
<main class="page-padding wrapper-main wrapper-max w-full">
    <div class="pt-20 flex flex-col gap-15">
        <div class="md:grid md:grid-cols-12 md:gap-5">
            <div class="flex flex-col gap-10 md:col-span-10 lg:col-span-8 xl:col-span-6">
                <h1 class="text-h1">Посетителям театра</h1>
                <?php if ($introHtml !== ''): ?>
                <div class="flex flex-col gap-8">
                    <?= $introHtml ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($currentPath !== '/for-visitors'): ?>
        <div class="flex flex-wrap gap-8">
            <?php foreach ($tabs as $tabPath => $tabTitle): ?>
                <?php $isActive = ($tabPath === $currentPath); ?>
                <a href="<?= htmlspecialcharsbx($tabPath . '/') ?>"
                   class="text-xxs uppercase tracking-widest link-hover whitespace-nowrap hover:opacity-100 <?= $isActive ? 'opacity-100 cursor-default pointer-events-none' : 'opacity-40' ?>">
                    <?= htmlspecialcharsbx($tabTitle) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="md:grid md:grid-cols-12 md:gap-5">
            <div class="flex flex-col gap-10 md:col-span-10 lg:col-span-8 xl:col-span-6">
                <?php if ($editUrl !== '' && isset($USER) && $USER->IsAdmin()): ?>
                <div>
                    <a href="<?= htmlspecialcharsbx($editUrl) ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;background:#f5f0e8;border:1px solid #c8b89a;border-radius:4px;font-size:12px;color:#5a4a3a;text-decoration:none;">
                        ✏ Редактировать содержимое
                    </a>
                </div>
                <?php endif; ?>
                <?= $contentHtml ?>
            </div>
        </div>
    </div>
</main>
