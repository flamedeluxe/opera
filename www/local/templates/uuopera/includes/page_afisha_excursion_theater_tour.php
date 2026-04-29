<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$code = (string) ($GLOBALS['UUOPERA_EXCURSION_CODE'] ?? 'jekskursija-puteshestvie-po-teatru');
$d = uuopera_excursion_get_data($code);

global $USER;
$uuoperaExcursionIsAdmin = is_object($USER) && $USER->IsAdmin();

if ($d['name'] === '') {
    ?>
<main class="flex flex-col gap-15">
    <div class="wrapper-main wrapper-max py-20 md:py-28 text-p2">
        <h1 class="text-h1 mb-6">Экскурсия</h1>
        <p class="max-w-2xl">Контент страницы не найден в инфоблоке «Экскурсии (афиша)». Создайте структуру и импортируйте данные с сайта-источника.</p>
        <ol class="list-decimal pl-6 mt-6 space-y-2 max-w-2xl">
            <li><code class="text-p3">php local/tools/uuopera_excursions_iblock_install.php</code></li>
            <li><code class="text-p3">php local/tools/uuopera_excursions_import_uuopera.php</code></li>
        </ol>
        <?php if ($uuoperaExcursionIsAdmin): ?>
            <p class="mt-6 opacity-80">
                Админка: Контент → Информационные блоки → uuopera.ru → Экскурсии (афиша). Символьный код элемента: <code class="text-p3"><?= htmlspecialcharsbx($code) ?></code>
            </p>
        <?php endif; ?>
    </div>
</main>
    <?php
    return;
}

$sessions = $d['sessions'];
$gallerySlides = $d['gallery'];
$sliderId = (string) $d['slider_id'];
$heroImage = (string) $d['hero_image'];
$heroSrcset = (string) $d['hero_srcset'];
$priceValue = (string) $d['price_value'];
$durationHero = (string) $d['duration_hero'];
$radarioAfishaKey = (string) $d['radario_afisha_key'];
$radarioAfishaKeyJs = $radarioAfishaKey !== ''
    ? json_encode($radarioAfishaKey, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS)
    : '';
$hasGallery = $gallerySlides !== [];
?>
<main class="flex flex-col gap-15">
    <div>
        <div class="relative" data-header-color-schema="transparent">

            <img width="1920" height="1080" src="<?= htmlspecialcharsbx($heroImage) ?>" class="absolute w-full h-full image-cover wp-post-image" alt="" decoding="async" loading="lazy"<?= $heroSrcset !== '' ? ' srcset="' . htmlspecialcharsbx($heroSrcset) . '"' : '' ?> sizes="auto, (max-width: 1920px) 100vw, 1920px" />

            <div class="absolute inset-0 bg-black-40"></div>
            <div class="flex portrait:min-h-[133vw] md:portrait:min-h-[100vw] min-h-[50vw]">
                <div class="relative flex-grow text-white min-h-[420px] md:min-h-[600px] wrapper-main wrapper-max flex items-end pb-12 pt-20 md:pb-16 md:portrait:pb-25">
                    <div class="flex flex-col gap-5 w-full">
                        <div class="flex gap-5 justify-between">
                            <div class="max-w-[600px]">
                                <h1 class="text-h1"><?= htmlspecialcharsbx((string) $d['name']) ?></h1>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-5 sm:justify-between sm:items-end text-h2">
                            <div class="max-w-[400px]">
                                <?php if ($priceValue !== '' || $durationHero !== ''): ?>
                                    <?php if ($priceValue !== ''): ?>
                                <p>Цена билета: <?= htmlspecialcharsbx($priceValue) ?></p>
                                    <?php endif; ?>
                                    <?php if ($durationHero !== ''): ?>
                                <p>Продолжительность: <?= htmlspecialcharsbx($durationHero) ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
<!--                             <a href="" target="_self" rel="nofollow" class="whitespace-nowrap link-hover inline-block">купить билет</a> -->
<?php if ($radarioAfishaKey !== ''): ?>
<!-- Кнопка Купить билет в карточке события. Радарио. Начало -->
                <div>
                <style>
                    .radario-button__main {
                            font-size: 12px !important;
                            font-weight: 400 !important;
                    }
                </style>
                                                     <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"купить билет","buttonPadding":"8px 2px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key": <?= $radarioAfishaKeyJs ?>});
                    </script>

                </div>
<!-- Кнопка Купить билет в карточке события. Радарио. Начало Конец -->
<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

                    <div class="grid gap-5 xl:grid-cols-2 wrapper-main wrapper-max">
                                                    <div class="sm:grid sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-3 sm:gap-4 xl:gap-0.5 xl:col-start-2">
                        <?php foreach ($sessions as $sessionRow): ?>
                            <?php
                            $dateLabel = (string) ($sessionRow[0] ?? '');
                            $eventId = (int) ($sessionRow[1] ?? 0);
                            if ($dateLabel === '' || $eventId <= 0) {
                                continue;
                            }
                            ?>
<!-- Блок Дата + кнопка Билеты для событий (сеансов) в карточке мероприятия Радарио. Начало -->
                            <div class="group py-4 px-6 md:px-4 xl:px-8 button-default text-p3 border-b border-current with-hover:border-beige hover:border-current">
                                <span class="flex justify-between gap-2.5 w-full">
                                    <span style="white-space:nowrap; margin-right:5px"><?= htmlspecialcharsbx($dateLabel) ?></span>
                                </span>
<script style="with-hover:opacity-0 group-hover:opacity-100 transition-opacity duration-300">radario.Widgets.Event({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"Билеты","buttonPadding":"8px 0px","buttonBorderRadius":"0","standalone":false,"createButton":true,"eventId":<?= $eventId ?>});
</script>
</div>
<!-- Блок Дата + кнопка Билеты для событий в карточке мероприятия Радарио. Конец -->
                        <?php endforeach; ?>
                                            </div>
                            </div>
            </div>


    <div class="flex flex-col gap-15 md:grid md:grid-cols-12 md:gap-x-5 wrapper-main wrapper-max w-full">

                    <div class="flex flex-col gap-8 justify-between md:grid md:grid-cols-7 lg:grid-cols-6 xl:grid-cols-5 md:col-span-7 lg:col-span-6 lg:gap-x-5 xl:col-span-5 lg:col-start-7 xl:col-start-7 text-p2">
                <div class="md:col-span-6 lg:col-span-5 xl:col-span-3 flex flex-col gap-[1em]">
                    <?= $d['body_html'] ?>
                    <?php if ($d['footer_duration'] !== ''): ?>
                    <p><?= htmlspecialcharsbx((string) $d['footer_duration']) ?></p>
                    <?php endif; ?>
                    <?php if ($d['footer_price'] !== ''): ?>
                    <p><?= htmlspecialcharsbx((string) $d['footer_price']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            </div>

    <?php if ($hasGallery): ?>
    <div class="flex flex-col gap-12 md:gap-15 2xl:gap-25 wrapper-main wrapper-max w-full">
        <div class="lazyblock-image-slider-default-<?= htmlspecialcharsbx($sliderId) ?> wp-block-lazyblock-image-slider-default">

    <div class="xl:grid xl:grid-cols-12 xl:gap-5">
        <div class="xl:col-span-9 xl:col-start-4 -mx-3 md:-mx-6 xl:ml-0 xl:-mr-6 3xl:mr-0">
            <div class="swiper slider-default" data-slider-default="<?= htmlspecialcharsbx($sliderId) ?>">
                <div class="swiper-wrapper">
                        <?php foreach ($gallerySlides as $src): ?>
                                            <div class="swiper-slide" data-slide>
                            <div class="relative pb-16/9">
                                <img decoding="async" src="<?= htmlspecialcharsbx((string) $src) ?>" alt="" class="absolute image-cover" data-img>
                            </div>
                        </div>
                        <?php endforeach; ?>
                                    </div>
            </div>
            <div class="mt-3 sm:w-[92%] xl:w-[78%] px-3 md:px-6 xl:px-0 ">
                <div class="border-y border-y-current flex">
                                            <div class="whitespace-nowrap min-w-25 flex-grow sm:flex-grow-0 flex justify-center items-center sm:border-r sm:border-r-current" data-slider-pagination="<?= htmlspecialcharsbx($sliderId) ?>"></div>
                                        <div class="text-p2 hidden sm:flex items-center sm:flex-grow px-5 min-h-13" data-slider-description="<?= htmlspecialcharsbx($sliderId) ?>"></div>
                                            <div class="flex gap-10 h-13 items-center border-l border-l-current px-8">
                            <button class="flex items-center group py-3 disabled:opacity-60 disabled:pointer-events-none" data-slider-prev-button="<?= htmlspecialcharsbx($sliderId) ?>">
                                <svg class="w-[8px] h-[10px] fill-current rotate-180">
                                    <use xlink:href="#arrow-tip"></use>
                                </svg>
                                <span class="w-10 h-0.5 bg-current group-hover:scale-x-125 origin-left transition-transform duration-300"></span>
                            </button>
                            <button class="flex items-center group py-3 disabled:opacity-60 disabled:pointer-events-none" data-slider-next-button="<?= htmlspecialcharsbx($sliderId) ?>">
                                <span class="w-10 h-0.5 bg-current group-hover:scale-x-125 origin-right transition-transform duration-300"></span>
                                <svg class="w-[8px] h-[10px] fill-current">
                                    <use xlink:href="#arrow-tip"></use>
                                </svg>
                            </button>
                        </div>
                                    </div>
            </div>
        </div>
    </div>

</div>
	</div>
    <?php endif; ?>

</main>
