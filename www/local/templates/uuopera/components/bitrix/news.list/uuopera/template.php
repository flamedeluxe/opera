<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */
/** @var array $arParams */

$iblockId = uuopera_news_iblock_id();

$uuNewsHasMore = false;
$uuNewsNextUrl = '';
$nav = $arResult['NAV_RESULT'] ?? null;
if (is_object($nav)) {
    $uuCurrentPage = max(1, (int) ($nav->NavPageNomer ?? 1));
    $uuPageCount = max(1, (int) ($nav->NavPageCount ?? 1));
    if ($uuCurrentPage < $uuPageCount) {
        $uuNewsHasMore = true;
        $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/category/news/'), '?') ?: '/category/news/';
        $params = $_GET;
        $params['PAGEN_1'] = (string) ($uuCurrentPage + 1);
        unset($params['page']);
        $uuNewsNextUrl = $path . '?' . http_build_query($params);
    }
}
?>
<div class="flex flex-col gap-16 2xl:gap-28 pt-32 wrapper-main wrapper-max">
    <h1 class="text-h1"><?= htmlspecialcharsbx($GLOBALS['UUOPERA_NEWS_LIST_TITLE'] ?? 'Новости') ?></h1>

    <?php if (empty($arResult['ITEMS'])): ?>
        <div class="text-p2 max-w-2xl">
            <p>Пока нет записей в инфоблоке новостей.</p>
            <p class="mt-4 opacity-80">
                Добавьте материалы в админке:
                <strong>Контент → Информационные блоки → [news] Новости сайта</strong>
                (ID инфоблока для этого шаблона: <?= (int) $iblockId ?>).
                У элементов должен быть заполнен <strong>символьный код</strong> (латиница, как в ЧПУ URL).
            </p>
        </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-y-15 gap-x-5 2xl:gap-y-25" list-paginated="news">
        <?php foreach ($arResult['ITEMS'] as $item): ?>
            <?php
            $detailUrl = $item['UUOPERA_DETAIL_URL'] ?? $item['DETAIL_PAGE_URL'] ?? '#';
            $name = $item['NAME'] ?? '';
            $previewSrc = '';
            if (!empty($item['PREVIEW_PICTURE'])) {
                if (is_array($item['PREVIEW_PICTURE'])) {
                    $previewSrc = (string) ($item['PREVIEW_PICTURE']['SRC'] ?? '');
                } else {
                    $file = CFile::GetFileArray((int) $item['PREVIEW_PICTURE']);
                    if (is_array($file)) {
                        $previewSrc = (string) ($file['SRC'] ?? '');
                    }
                }
            }
            $dateStr = '';
            if (!empty($item['DISPLAY_ACTIVE_FROM'])) {
                $dateStr = (string) $item['DISPLAY_ACTIVE_FROM'];
            } elseif (!empty($item['ACTIVE_FROM'])) {
                $dateStr = ConvertDateTime($item['ACTIVE_FROM'], 'DD.MM.YYYY');
            }
            $previewHtml = uuopera_afisha_card_teaser_html(
                (string) ($item['PREVIEW_TEXT'] ?? ''),
                (string) ($item['DETAIL_TEXT'] ?? '')
            );
            ?>
        <div class="flex flex-col gap-5 relative group">
            <a href="<?= htmlspecialcharsbx($detailUrl) ?>" class="group-hover:[&_img]:scale-105 [&_img]:transition-transform [&_img]:duration-600 link-stretching">
                <div class="block relative pb-16/9 overflow-hidden bg-brown-dark/10">
                    <?php if ($previewSrc !== ''): ?>
                        <img src="<?= htmlspecialcharsbx($previewSrc) ?>" class="absolute image-cover wp-post-image" alt="<?= htmlspecialcharsbx($name) ?>" decoding="async" loading="lazy" />
                    <?php endif; ?>
                </div>
            </a>
            <div class="flex flex-col gap-5 [&_a]:relative">
                <?php if ($dateStr !== ''): ?>
                <div class="text-xs"><?= htmlspecialcharsbx($dateStr) ?></div>
                <?php endif; ?>
                <h3 class="text-h2"><?= htmlspecialcharsbx($name) ?></h3>
                <?php if ($previewHtml !== ''): ?>
                <div class="text-p3"><?= $previewHtml ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($uuNewsHasMore && $uuNewsNextUrl !== ''): ?>
    <div class="flex justify-center" data-pagination="news">
        <a href="<?= htmlspecialcharsbx($uuNewsNextUrl) ?>"
           class="group text-p3 lowercase button-default p-5 w-full lg:max-w-[680px] border-b border-current"
           data-pagination-load-next="news">
            <span class="relative px-4 link-hover">
                <span>загрузить еще</span>
                <span class="absolute top-[50%] left-full -translate-y-[50%] hidden group-[&.loading]:block">
                    <div class="spinner w-4 h-4 border-2"></div>
                </span>
            </span>
        </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
