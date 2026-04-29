<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/*
 * Как на https://uuopera.ru/ : слайдер из 4 спектаклей/концертов (без экскурсии),
 * сетка — экскурсии сверху, затем остальное. ИБ «Главная: слайды» переопределяет только слайдер.
 */
$homeBundle = uuopera_afisha_home_bundle(12, 4);
$homeAfishaCards = $homeBundle['grid'];

// Сначала пробуем взять слайды из специального ИБ (если он настроен)
$cmsSlides = uuopera_cms_home_slides_list();

// Проверяем, что слайды из ИБ относятся к афише (содержат ссылки на /afisha/)
$hasAfishaLinks = false;
foreach ($cmsSlides as $slide) {
    if (stripos((string) ($slide['link_url'] ?? ''), '/afisha/') !== false) {
        $hasAfishaLinks = true;
        break;
    }
}

// Если ИБ слайдов пуст или не содержит ссылок на афишу — используем слайды из афиши
if ($cmsSlides === [] || !$hasAfishaLinks) {
    $homeSlides = $homeBundle['slides'];
} else {
    $homeSlides = $cmsSlides;
}

// Если всё ещё пусто — показываем заглушку
if ($homeSlides === []) {
    $homeSlides = [
        [
            'name' => 'Афиша театра',
            'link_url' => '/afisha/',
            'subtext_html' => '<p>Добавьте события: импорт <code class="text-p3">php local/tools/uuopera_afisha_bulk_import_uuopera.php</code> или элементы в инфоблоке «События афиши».</p>',
            'age_mark' => '',
            'radario_afisha_key' => '',
            'intickets_url' => '',
            'image' => '',
            'srcset' => '',
        ],
    ];
}
?>
<div class="flex flex-col gap-20">

    <div class="bg-brown-dark relative overflow-hidden" data-header-color-schema="transparent" role="region" aria-label="Рекомендуемые события афиши">
        <div class="swiper" data-event-slider>
            <div class="swiper-wrapper">
                <?php foreach ($homeSlides as $idx => $slide) {
                    $sName = htmlspecialchars((string) ($slide['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $sLink = htmlspecialchars((string) ($slide['link_url'] ?? '/afisha/'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $sImg = (string) ($slide['image'] ?? '');
                    $sSrcset = (string) ($slide['srcset'] ?? '');
                    $age = htmlspecialchars(trim((string) ($slide['age_mark'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $sub = (string) ($slide['subtext_html'] ?? '');
                    $intickets = trim((string) ($slide['intickets_url'] ?? ''));
                    $radKey = (string) ($slide['radario_afisha_key'] ?? '');
                    ?>
                    <div class="swiper-slide" data-event-slider-slide>
                        <a href="<?= $sLink ?>" class="block relative text-white"
                            <?php if ($intickets !== '') { ?>
                                data-intickets-url="<?= htmlspecialchars($intickets, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                            <?php } ?>>

                            <?php if ($sImg !== '') { ?>
                                <img width="1920" height="1080"
                                     src="<?= htmlspecialchars($sImg, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                     class="absolute image-cover wp-post-image" alt="<?= $sName ?>" decoding="async"
                                     <?php if ($idx === 0) { ?>fetchpriority="high"<?php } ?>
                                     <?php if ($sSrcset !== '') { ?>srcset="<?= htmlspecialchars($sSrcset, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" sizes="(max-width: 1920px) 100vw, 1920px"<?php } ?> />
                            <?php } ?>

                            <div class="absolute inset-0 bg-black-40"></div>

                            <div class="flex portrait:min-h-[133vw] md:portrait:min-h-[100vw] min-h-[50vw]">
                                <div class="flex flex-col flex-grow justify-end min-h-[420px] md:min-h-[600px] pb-36 relative wrapper-main wrapper-max">
                                    <div class="flex flex-col gap-7">
                                        <div class="flex justify-between gap-10 md:grid md:grid-cols-2 xl:grid-cols-12 md:gap-5">
                                            <div class="xl:col-span-4">
                                                <h2 class="text-line"><?= $sName ?></h2>
                                            </div>
                                            <?php if ($age !== '') { ?>
                                                <div class="flex justify-end text-h2 pt-1 xl:col-start-12"><?= $age ?></div>
                                            <?php } ?>
                                        </div>
                                        <?php if ($sub !== '') { ?>
                                            <div class="sm:grid md:grid-cols-2 xl:grid-cols-12 sm:gap-5">
                                                
                                            </div>
                                        <?php } ?>
                                        <?php if ($radKey !== '') {
                                            $payload = [
                                                'params' => [
                                                    'accentColor' => 'rgba(30, 21, 18, 1)',
                                                    'textBtnColor' => '#FFFFFF',
                                                    'textColor' => '#3D3634',
                                                    'backgroundColor' => 'rgba(245, 239, 235, 1)',
                                                ],
                                                'buttonText' => 'купить билет',
                                                'buttonPadding' => '8px 2px',
                                                'buttonBorderRadius' => '0',
                                                'standalone' => false,
                                                'createButton' => true,
                                                'key' => $radKey,
                                            ];
                                            ?>
                                            <div class="relative z-10 mt-4 text-sm uppercase">
                                                <script>radario.Widgets.Afisha(<?= json_encode($payload, JSON_UNESCAPED_UNICODE) ?>);</script>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
        <div class="absolute left-0 w-full bottom-12 landscape:bottom-7 md:landscape:bottom-12 pointer-events-none z-1">
            <div class="wrapper-main wrapper-max md:grid md:grid-cols-2 md:gap-5">
                <div class="flex border-y border-y-white-30 md:border-none text-white text-sm uppercase md:col-start-2 xl:justify-between">
                    <div class="flex items-center justify-between gap-3 xl:gap-25 pl-1 pr-7 flex-grow xl:flex-grow-0 pointer-events-auto">
                        <div class="whitespace-nowrap min-w-10" data-event-slider-pagination></div>
                        <div class="flex gap-10">
                            <button class="flex items-center group py-3 disabled:opacity-60 disabled:cursor-auto disabled:pointer-events-none" data-event-slider-prev-button>
                                <svg class="w-[8px] h-[10px] fill-white rotate-180">
                                    <use xlink:href="#arrow-tip"></use>
                                </svg>
                                <span class="w-10 h-0.5 bg-white group-hover:scale-x-125 origin-left transition-transform duration-300"></span>
                            </button>
                            <button class="flex items-center group py-3 disabled:opacity-60 disabled:cursor-auto disabled:pointer-events-none" data-event-slider-next-button>
                                <span class="w-10 h-0.5 bg-white group-hover:scale-x-125 origin-right transition-transform duration-300"></span>
                                <svg class="w-[8px] h-[10px] fill-white">
                                    <use xlink:href="#arrow-tip"></use>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wrapper-main wrapper-max">
        <div class="flex flex-col gap-20">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-y-15 gap-x-5 2xl:gap-y-25">
                <?php
                foreach ($homeAfishaCards as $card) {
                    include __DIR__ . '/_afisha_list_card.php';
                }
                ?>
            </div>
        </div>
        <a href="/afisha/" class="group text-p2 button-default gap-6">
            <span>ко всем событиям</span>
            <span class="flex items-center -rotate-45 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-300">
                <span class="w-3 border-b border-current"></span>
                <svg class="w-[8px] h-[10px] fill-current">
                    <use xlink:href="#arrow-tip"></use>
                </svg>
            </span>
        </a>
    </div>

    <?php include __DIR__ . '/_main_home_news_block.inc.php'; ?>

</div>

<?php include __DIR__ . '/_main_home_lower.inc.php'; ?>

