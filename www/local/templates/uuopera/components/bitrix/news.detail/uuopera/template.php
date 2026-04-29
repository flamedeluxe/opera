<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */

if (empty($arResult)) {
    @define('ERROR_404', 'Y');
    CHtTp::SetStatus('404 Not Found');
    echo '<div class="wrapper-main wrapper-max pt-32"><p class="text-p1">Новость не найдена.</p></div>';
    return;
}

$item = $arResult;
$name = (string) ($item['NAME'] ?? '');
$detailText = (string) ($item['DETAIL_TEXT'] ?? $item['PREVIEW_TEXT'] ?? '');
$dateStr = '';
if (!empty($item['DISPLAY_ACTIVE_FROM'])) {
    $dateStr = (string) $item['DISPLAY_ACTIVE_FROM'];
} elseif (!empty($item['ACTIVE_FROM'])) {
    $dateStr = (string) FormatDate('j.m.Y', MakeTimeStamp($item['ACTIVE_FROM'], FORMAT_DATETIME));
}

$imgSrc = '';
$imgW = 2560;
$imgH = 1707;
if (!empty($item['DETAIL_PICTURE'])) {
    $file = CFile::GetFileArray((int) $item['DETAIL_PICTURE']);
    if (is_array($file) && ($file['SRC'] ?? '') !== '') {
        $imgSrc = (string) $file['SRC'];
        if (!empty($file['WIDTH'])) {
            $imgW = (int) $file['WIDTH'];
        }
        if (!empty($file['HEIGHT'])) {
            $imgH = (int) $file['HEIGHT'];
        }
    }
}
if ($imgSrc === '' && !empty($item['PREVIEW_PICTURE'])) {
    $file = CFile::GetFileArray((int) $item['PREVIEW_PICTURE']);
    if (is_array($file) && ($file['SRC'] ?? '') !== '') {
        $imgSrc = (string) $file['SRC'];
    }
}
?>
<main class="page-padding flex flex-col gap-20 2xl:gap-25">
    <div class="wrapper-main wrapper-max w-full pt-3 lg:pt-25">
        <div class="flex flex-col gap-8 lg:grid lg:grid-cols-2 lg:gap-5">
            <div class="lg:order-1">
                <?php if ($imgSrc !== ''): ?>
                <div class="relative pb-16/9">
                    <img width="<?= $imgW ?>" height="<?= $imgH ?>" src="<?= htmlspecialcharsbx($imgSrc) ?>"
                        class="absolute image-cover wp-post-image" alt="<?= htmlspecialcharsbx($name) ?>" decoding="async" loading="lazy" />
                </div>
                <?php endif; ?>
            </div>
            <div class="md:grid md:grid-cols-12 lg:grid-cols-6 md:gap-5">
                <div class="flex flex-col gap-5 md:gap-16 md:col-span-10 lg:col-span-5">
                    <h1 class="text-h1"><?= htmlspecialcharsbx($name) ?></h1>
                    <?php if ($dateStr !== ''): ?>
                    <div class="text-p3"><?= htmlspecialcharsbx($dateStr) ?></div>
                    <?php endif; ?>
                    <?php if ($detailText !== ''): ?>
                    <div class="text-p1"><?= $detailText ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
