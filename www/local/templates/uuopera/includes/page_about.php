<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$about = uuopera_about_get_data();
$timeline = $about['timeline'];
$mission = $about['mission_slides'];
$htmlBlocks = $about['html_blocks'];
$hasAny = $timeline !== [] || $mission !== [] || $htmlBlocks !== [];
?>
<div class="wrapper-main wrapper-max py-25 2xl:py-40">
    <div class="flex flex-col gap-12 lg:grid lg:grid-cols-2">
        <div class="flex flex-col gap-7 justify-between">
            <h1 class="text-h1">О театре</h1>
            <div class="py-5 w-full relative overflow-hidden">
                <div class="flex gap-16 justify-between sm:justify-start min-w-full">
                    <a href="#history" class="text-xxs tracking-widest uppercase">История</a>
                    <a href="#mission" class="text-xxs tracking-widest uppercase">Миссия</a>
                    <a href="#persons" class="text-xxs tracking-widest uppercase">Персоны</a>
                </div>
            </div>
            <div class="flex flex-col gap-[1em] text-p1">
                <p>Первый и крупнейший театр оперы и балета на Дальнем Востоке. Культурный бренд всего региона, место, куда жители и гости Улан-Удэ приходят приобщиться к традициям бурятской, российской и мировой музыкальной культуры. Сохраняя традиции, театр идёт в ногу со временем и открыт для экспериментов и реализации творческого потенциала талантливых деятелей искусства.</p>
                <p>Каждый сезон на сцене проходят более 200 оперных и балетных спектаклей, концертов и постановок для детей. Бурятский государственный ордена Ленина академический театр оперы и балета имени народного артиста СССР Гомбо Цыдынжапова &mdash; флагман культурной жизни Республики Бурятия.</p>
            </div>
        </div>
        <div>
            <div class="relative pb-[50%] md:portrait:pb-[78%] lg:pb-0 lg:h-full lg:min-h-[480px] 2xl:min-h-[560px]">
                <img width="1920" height="1080"
                     src="https://uuopera.ru/wp-content/uploads/2024/12/teatr-1920h1080.jpg"
                     class="absolute image-cover wp-post-image"
                     alt="" decoding="async" loading="lazy"
                     srcset="https://uuopera.ru/wp-content/uploads/2024/12/teatr-1920h1080.jpg 1920w, https://uuopera.ru/wp-content/uploads/2024/12/teatr-1920h1080-1024x576.jpg 1024w, https://uuopera.ru/wp-content/uploads/2024/12/teatr-1920h1080-768x432.jpg 768w, https://uuopera.ru/wp-content/uploads/2024/12/teatr-1920h1080-600x338.jpg 600w"
                     sizes="auto, (max-width: 1920px) 100vw, 1920px">
            </div>
        </div>
    </div>
</div>
<div class="flex flex-col gap-20">
    <?php if (!$hasAny) { ?>
        <div class="wrapper-main wrapper-max py-20">
            <p class="text-p2 max-w-2xl">Контент раздела переносится в инфоблок «О театре: блоки». Выполните <code class="text-xs bg-beige px-1">php -f local/tools/uuopera_cms_iblocks_install.php</code> и заполните элементы (типы: timeline, mission, html).</p>
        </div>
    <?php } ?>

    <?php if ($timeline !== []) { ?>
        <div class="lazyblock-history-Z1JIMWh wp-block-lazyblock-history">
            <div class="wrapper-main wrapper-max" id="history">
                <?php foreach ($timeline as $row) {
                    $y = htmlspecialchars((string) ($row['year'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $t = htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $side = (string) ($row['side_image'] ?? '');
                    ?>
                    <div class="border-t border-t-brown py-4 group cursor-pointer" data-discloser>
                        <div class="grid grid-cols-2 sm:grid-cols-12 gap-5 items-start h-9 md:h-12 overflow-hidden transition-[height] duration-400" data-discloser-content>
                            <div class="text-line sm:col-span-2 xl:col-span-1"><?= $y ?></div>
                            <div class="text-h2 sm:col-span-2 translate-y-[calc(1.875rem*0.1)] md:translate-y-[calc(2.625rem*0.1)]"><?= $t ?></div>
                            <?php if ($side !== '') {
                                $caption = htmlspecialchars((string) ($row['side_caption'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                ?>
                                <div class="sm:col-span-2 xl:col-span-1 sm:opacity-0 sm:group-[&.open]:opacity-100 transition-opacity duration-400">
                                    <div class="flex flex-col gap-4">
                                        <img decoding="async" src="<?= htmlspecialchars($side, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" alt="<?= $caption ?>">
                                        <?php if ($caption !== '') { ?>
                                            <div class="text-p3"><?= $caption ?></div>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="flex flex-col gap-[1em] text-p2 col-span-2 sm:col-span-6 xl:col-span-4 2xl:col-span-3 sm:col-start-7 xl:col-start-7 2xl:col-start-7">
                                <?= $row['body_html'] ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

    <?php if ($mission !== []) { ?>
        <div class="lazyblock-mission-23MzFh wp-block-lazyblock-mission">
            <div class="w-full" id="mission">
                <div class="swiper w-full slider-about" data-slider-about>
                    <div class="swiper-wrapper">
                        <?php foreach ($mission as $ms) {
                            $th = htmlspecialchars((string) ($ms['theme'] ?? 'white'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $diag = (string) ($ms['diagram_src'] ?? '');
                            $lead = htmlspecialchars((string) ($ms['lead'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            ?>
                            <div class="swiper-slide <?= $th ?>" data-theme="<?= $th ?>" data-slide>
                                <div class="flex flex-col justify-between gap-8 md:gap-16 md:portrait:gap-25 min-h-full pt-20 wrapper-main wrapper-max w-full">
                                    <?php if ($lead !== '') { ?>
                                        <div class="text-lg md:text-3xl xl:text-4xl tracking-wider xl:tracking-wide uppercase text-justify">
                                            <?= $lead ?>
                                        </div>
                                    <?php } ?>
                                    <div class="flex flex-col md:grid md:grid-cols-12 gap-8 md:gap-5 md:items-end lg:items-start text-p2 relative pb-25 lg:pb-10">
                                        <div class="flex flex-col gap-8 relative z-1 md:col-span-5 lg:col-span-3 lg:col-start-4">
                                            <div class="flex flex-col gap-8 text-balance">
                                                <?= $ms['body_html'] ?>
                                            </div>
                                        </div>
                                        <?php if ($diag !== '') { ?>
                                            <div class="absolute md:static bottom-4 w-full opacity-20 md:opacity-100 md:col-span-5 md:col-start-8 lg:col-span-3 lg:col-start-10">
                                                <img decoding="async" src="<?= htmlspecialchars($diag, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" alt="" class="w-full">
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="slider-about-pagination wrapper-main wrapper-max w-full absolute -left-1 -right-1 bottom-10 flex gap-7 z-1" data-slider-about-pagination></div>
                </div>
            </div>
        </div>
    <?php } ?>

    <?php foreach ($htmlBlocks as $chunk) { ?>
        <div class="wrapper-main wrapper-max text-p2">
            <?= $chunk ?>
        </div>
    <?php } ?>
</div>
