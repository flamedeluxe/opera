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
$name        = (string) ($item['NAME'] ?? '');
$previewHtml = uuopera_afisha_card_teaser_html(
    (string) ($item['PREVIEW_TEXT'] ?? ''),
    (string) ($item['DETAIL_TEXT'] ?? '')
);
$detailText  = uuopera_html_decode_content((string) ($item['DETAIL_TEXT'] ?? ''));
$dateStr = '';
if (!empty($item['DISPLAY_ACTIVE_FROM'])) {
    $dateStr = (string) $item['DISPLAY_ACTIVE_FROM'];
} elseif (!empty($item['ACTIVE_FROM'])) {
    $dateStr = (string) FormatDate('j.m.Y', MakeTimeStamp($item['ACTIVE_FROM'], FORMAT_DATETIME));
}

// The component runs getFieldImageData() — picture fields are arrays with SRC/WIDTH/HEIGHT,
// not raw file IDs. Fall back to CFile::GetFileArray for integer IDs (legacy data).
$imgSrc = '';
$imgW   = 0;
$imgH   = 0;
foreach (['DETAIL_PICTURE', 'PREVIEW_PICTURE'] as $picField) {
    if ($imgSrc !== '' || empty($item[$picField])) {
        continue;
    }
    $pic = $item[$picField];
    if (is_array($pic) && ($pic['SRC'] ?? '') !== '') {
        $imgSrc = (string) $pic['SRC'];
        $imgW   = (int) ($pic['WIDTH'] ?? 0);
        $imgH   = (int) ($pic['HEIGHT'] ?? 0);
    } elseif (is_numeric($pic)) {
        $file = CFile::GetFileArray((int) $pic);
        if (is_array($file) && ($file['SRC'] ?? '') !== '') {
            $imgSrc = (string) $file['SRC'];
            $imgW   = (int) ($file['WIDTH'] ?? 0);
            $imgH   = (int) ($file['HEIGHT'] ?? 0);
        }
    }
}
?>
<main class="page-padding flex flex-col gap-20 2xl:gap-25">
    <div class="wrapper-main wrapper-max w-full pt-3 lg:pt-25">
        <div class="flex flex-col gap-8 lg:grid lg:grid-cols-2 lg:gap-5">
            <div class="lg:order-1">
                <?php if ($imgSrc !== ''): ?>
                <div class="relative pb-16/9">
                    <img <?= $imgW > 0 ? 'width="' . $imgW . '" ' : '' ?><?= $imgH > 0 ? 'height="' . $imgH . '" ' : '' ?>src="<?= htmlspecialcharsbx($imgSrc) ?>"
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
                    <?php if ($previewHtml !== ''): ?>
                    <div class="text-p1"><?= $previewHtml ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php if ($detailText !== ''): ?>
    <div class="bg-beige" data-header-color-schema="beige">
        <div class="wrapper-main wrapper-max w-full pt-12 pb-20 2xl:pt-20 flex flex-col gap-5">
            <?= $detailText ?>
        </div>
    </div>
    <?php endif; ?>
</main>
