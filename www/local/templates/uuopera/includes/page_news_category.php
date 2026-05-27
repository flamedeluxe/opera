<?php

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */
$sectionCode  = (string) ($GLOBALS['UUOPERA_NEWS_CATEGORY_CODE'] ?? '');
$sectionTitle = 'Новости';
$sectionId    = 0;

if ($sectionCode !== '' && \Bitrix\Main\Loader::includeModule('iblock')) {
    $secRow = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => uuopera_news_iblock_id(), '=CODE' => $sectionCode, 'ACTIVE' => 'Y'],
        false,
        ['ID', 'NAME']
    )->Fetch();
    if ($secRow) {
        $sectionId    = (int) $secRow['ID'];
        $sectionTitle = (string) $secRow['NAME'];
        $APPLICATION->SetTitle($sectionTitle . ' - Бурятский театр оперы и балета');
        $APPLICATION->SetPageProperty('title', $sectionTitle . ' - Бурятский театр оперы и балета');
    }
}

$items       = [];
$navPageCount = 1;
$navPageNomer = 1;
if (\Bitrix\Main\Loader::includeModule('iblock')) {
    $iblockId = uuopera_news_iblock_id();
    $filter   = [
        'IBLOCK_ID'  => $iblockId,
        'ACTIVE'     => 'Y',
        'CHECK_DATES' => 'Y',
    ];
    if ($sectionId > 0) {
        $filter['SECTION_ID']          = $sectionId;
        $filter['INCLUDE_SUBSECTIONS'] = 'Y';
    }

    $navPage = max(1, (int) ($_REQUEST['PAGEN_1'] ?? 1));
    $res = CIBlockElement::GetList(
        ['ACTIVE_FROM' => 'DESC'],
        $filter,
        false,
        ['nPageSize' => 12, 'iNumPage' => $navPage],
        ['ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PREVIEW_PICTURE', 'ACTIVE_FROM', 'CODE', 'DATE_ACTIVE_FROM']
    );

    $navPageCount = (int) ($res->NavPageCount ?? 1);
    $navPageNomer = (int) ($res->NavPageNomer ?? $navPage);

    while ($row = $res->GetNext()) {
        $code = trim((string) ($row['CODE'] ?? ''));
        $from = $row['ACTIVE_FROM'] ?? $row['DATE_ACTIVE_FROM'] ?? '';
        $ts   = $from !== '' ? (int) MakeTimeStamp($from) : 0;
        if ($ts <= 0 && $from !== '') {
            $ts = (int) strtotime($from);
        }
        $row['UUOPERA_DETAIL_URL'] = ($ts > 0 && $code !== '')
            ? '/' . date('Y/m/d', $ts) . '/' . $code . '/'
            : '/category/news/detail/?id=' . (int) $row['ID'];
        $items[] = $row;
    }
}
?>
<div class="flex flex-col gap-16 2xl:gap-28 pt-32 wrapper-main wrapper-max">
    <h1 class="text-h1"><?= htmlspecialcharsbx($sectionTitle) ?></h1>

    <?php if (empty($items)): ?>
        <div class="text-p2 max-w-2xl">
            <p>Материалов в этом разделе пока нет.</p>
        </div>
    <?php else: ?>
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-y-15 gap-x-5 2xl:gap-y-25">
        <?php foreach ($items as $item): ?>
            <?php
            $detailUrl   = $item['UUOPERA_DETAIL_URL'] ?? '#';
            $name        = $item['NAME'] ?? '';
            $previewSrc  = '';
            if (!empty($item['PREVIEW_PICTURE'])) {
                $file = CFile::GetFileArray((int) $item['PREVIEW_PICTURE']);
                if (is_array($file)) {
                    $previewSrc = (string) ($file['SRC'] ?? '');
                }
            }
            $dateStr     = '';
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
    <?php endif; ?>

    <?php if ($navPageCount > 1): ?>
    <?php
    $currentUrl = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $buildUrl   = static function (int $page) use ($currentUrl): string {
        $params = $_GET;
        if ($page <= 1) {
            unset($params['PAGEN_1']);
        } else {
            $params['PAGEN_1'] = $page;
        }
        return $currentUrl . ($params ? '?' . http_build_query($params) : '');
    };
    $window = 2; // pages shown around current
    ?>
    <nav class="flex items-center justify-center gap-1 flex-wrap pt-4 pb-8" aria-label="Страницы">
        <?php if ($navPageNomer > 1): ?>
        <a href="<?= htmlspecialcharsbx($buildUrl($navPageNomer - 1)) ?>"
           class="inline-flex items-center justify-center w-10 h-10 rounded border border-current text-sm hover:bg-brown-dark hover:text-white transition-colors"
           aria-label="Предыдущая страница">&#8592;</a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $navPageCount; $p++):
            $near = abs($p - $navPageNomer) <= $window || $p === 1 || $p === $navPageCount;
            $gap  = !$near && (abs($p - $navPageNomer) === $window + 1);
            if (!$near && !$gap) continue;
        ?>
            <?php if ($gap): ?>
            <span class="inline-flex items-center justify-center w-10 h-10 text-sm opacity-40">…</span>
            <?php elseif ($p === $navPageNomer): ?>
            <span class="inline-flex items-center justify-center w-10 h-10 rounded bg-brown-dark text-white text-sm font-bold"
                  aria-current="page"><?= $p ?></span>
            <?php else: ?>
            <a href="<?= htmlspecialcharsbx($buildUrl($p)) ?>"
               class="inline-flex items-center justify-center w-10 h-10 rounded border border-current text-sm hover:bg-brown-dark hover:text-white transition-colors"><?= $p ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($navPageNomer < $navPageCount): ?>
        <a href="<?= htmlspecialcharsbx($buildUrl($navPageNomer + 1)) ?>"
           class="inline-flex items-center justify-center w-10 h-10 rounded border border-current text-sm hover:bg-brown-dark hover:text-white transition-colors"
           aria-label="Следующая страница">&#8594;</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
</div>
