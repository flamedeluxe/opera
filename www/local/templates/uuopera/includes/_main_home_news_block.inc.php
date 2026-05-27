<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Динамический блок новостей главной страницы.
 * Первый элемент — большая карточка, остальные — строки.
 */

$_uuMonths = [
    1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
    5 => 'мая',    6 => 'июня',    7 => 'июля',  8 => 'августа',
    9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
];

$_uuNewsItems = [];

if (\Bitrix\Main\Loader::includeModule('iblock')) {
    $iblockId = uuopera_news_iblock_id();
    if ($iblockId > 0) {
        $res = CIBlockElement::GetList(
            ['ACTIVE_FROM' => 'DESC', 'ID' => 'DESC'],
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
            false,
            ['nTopCount' => 4],
            ['ID', 'NAME', 'CODE', 'DATE_ACTIVE_FROM', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'PREVIEW_PICTURE']
        );
        while ($row = $res->GetNext()) {
            $dateStr = (string) ($row['DATE_ACTIVE_FROM'] ?? '');
            // формат "31.03.2026" или "31.03.2026 00:00:00"
            $ts = strtotime($dateStr);
            if ($ts === false) {
                $ts = time();
            }
            $day   = (int) date('j', $ts);
            $month = (int) date('n', $ts);
            $year  = (int) date('Y', $ts);
            $dayMonth = $day . ' ' . ($_uuMonths[$month] ?? '');
            $dateUrl  = date('Y/m/d', $ts);
            $code     = (string) ($row['CODE'] ?? '');
            $url      = $code !== '' ? '/' . $dateUrl . '/' . $code . '/' : '/category/news/';

            $picId = (int) ($row['PREVIEW_PICTURE'] ?? 0);
            $picSrc = '';
            if ($picId > 0) {
                $p = CFile::GetPath($picId);
                if (is_string($p) && $p !== '') {
                    $picSrc = $p;
                }
            }

            $_uuNewsItems[] = [
                'url'       => $url,
                'year'      => $year,
                'dayMonth'  => $dayMonth,
                'name'      => (string) ($row['NAME'] ?? ''),
                'excerpt'   => uuopera_afisha_card_teaser_html(
                    (string) ($row['PREVIEW_TEXT'] ?? ''),
                    (string) ($row['DETAIL_TEXT'] ?? '')
                ),
                'img'       => $picSrc,
            ];
        }
    }
}

if ($_uuNewsItems === []) {
    return;
}

$_uuArrow = '<span class="flex items-center -rotate-45 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-300">'
    . '<span class="w-3 border-b border-current"></span>'
    . '<svg class="w-[8px] h-[10px] fill-current"><use xlink:href="#arrow-tip"></use></svg>'
    . '</span>';

?>
<div class="bg-beige" data-header-color-schema="beige">
    <div class="wrapper-main wrapper-max pb-15">
        <?php foreach ($_uuNewsItems as $_uuIdx => $_uuItem):
            $href  = htmlspecialchars($_uuItem['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = htmlspecialchars($_uuItem['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $year  = htmlspecialchars((string) $_uuItem['year'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $dm    = htmlspecialchars($_uuItem['dayMonth'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $img   = $_uuItem['img'];
            $exc   = $_uuItem['excerpt'];
        ?>
            <?php if ($_uuIdx === 0): ?>
            <a href="<?= $href ?>" class="group flex flex-col gap-4 lg:grid lg:grid-cols-2 lg:gap-5 text-p2 py-5 md:py-15 border-b border-current">
                <div class="flex gap-10 xl:gap-20">
                    <div><?= $year ?></div>
                    <div><?= $dm ?></div>
                </div>
                <div class="flex flex-col gap-4">
                    <?php if ($img !== ''): ?>
                    <div class="block relative pb-16/9 md:pb-[45%] overflow-hidden group-hover:[&_img]:scale-105 [&_img]:transition-transform [&_img]:duration-600">
                        <img src="<?= htmlspecialchars($img, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" class="absolute image-cover" alt="<?= $title ?>" decoding="async" />
                    </div>
                    <?php endif ?>
                    <div class="flex flex-col gap-10">
                        <div class="flex justify-between gap-10">
                            <h3><?= $title ?></h3>
                            <button class="text-p2 button-default gap-6">
                                <span class="hidden md:block">подробнее</span>
                                <?= $_uuArrow ?>
                            </button>
                        </div>
                        <?php if ($exc !== ''): ?>
                        <div class="hidden md:block">
                            <div class="max-w-[340px]"><?= $exc ?></div>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            </a>
            <?php else: ?>
            <a href="<?= $href ?>" class="group flex justify-between gap-10 md:grid md:grid-cols-12 md:gap-5 md:items-center text-p2 py-3 border-b border-current xl:min-h-12">
                <div class="flex flex-col gap-2 md:col-span-6 xl:col-span-9 xl:grid xl:grid-cols-9 xl:gap-5">
                    <div class="flex gap-10 xl:gap-20 xl:col-span-6">
                        <div><?= $year ?></div>
                        <div><?= $dm ?></div>
                    </div>
                    <div class="xl:col-span-3">
                        <h3><?= $title ?></h3>
                    </div>
                </div>
                <?php if ($img !== ''): ?>
                <div class="hidden md:block md:col-span-3 xl:col-span-2 md:col-start-8 lg:col-start-7 xl:col-start-10 relative">
                    <div class="relative w-full xl:absolute xl:top-[50%] xl:left-0 xl:-translate-y-[40%] xl:group-hover:-translate-y-[50%] xl:opacity-0 xl:group-hover:opacity-100 transition-[transform,opacity] duration-600 pb-[45%] overflow-hidden pointer-events-none">
                        <img src="<?= htmlspecialchars($img, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" class="absolute image-cover" alt="<?= $title ?>" decoding="async" />
                    </div>
                </div>
                <?php endif ?>
                <div class="md:col-start-12 flex justify-end">
                    <button class="text-p2 button-default gap-6">
                        <?= $_uuArrow ?>
                    </button>
                </div>
            </a>
            <?php endif ?>
        <?php endforeach ?>
    </div>
</div>
