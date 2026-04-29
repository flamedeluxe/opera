<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$projectCode = (string) ($GLOBALS['UUOPERA_PROJECT_CODE'] ?? '');
if ($projectCode !== '') {
    $proj = uuopera_cms_project_by_code($projectCode);
    ?>
    <div class="flex flex-col gap-10 pt-32 wrapper-main wrapper-max" data-header-color-schema="blue">
        <?php if ($proj === null) { ?>
            <h1 class="text-h1">Проект</h1>
            <p class="text-p2 max-w-2xl">Материал не найден. Добавьте элемент в инфоблок «Проекты» с символьным кодом <?= htmlspecialchars($projectCode, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>.</p>
        <?php } else {
            $pname = htmlspecialchars($proj['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            ?>
            <h1 class="text-h1"><?= $pname ?></h1>
            <?php if ($proj['image'] !== '') { ?>
                <div class="block relative pb-16/9 overflow-hidden max-w-5xl">
                    <img width="1920" height="1080" src="<?= htmlspecialchars($proj['image'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                         class="absolute image-cover wp-post-image" alt="<?= $pname ?>" decoding="async"
                         <?php if ($proj['srcset'] !== '') { ?>srcset="<?= htmlspecialchars($proj['srcset'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" sizes="(max-width: 1920px) 100vw, 1920px"<?php } ?> />
                </div>
            <?php } ?>
            <div class="text-p2 max-w-4xl">
                <?= $proj['detail_html'] ?>
            </div>
        <?php } ?>
    </div>
    <?php
    return;
}

$projects = uuopera_cms_projects_list();
?>
<div class="flex flex-col gap-10 pt-32 wrapper-main wrapper-max" data-header-color-schema="blue">
    <h1 class="text-h1">Проекты</h1>

    <div class="grid xl:grid-cols-2 gap-5 xl:gap-y-20">
        <?php foreach ($projects as $p) {
            $url = htmlspecialchars($p['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $title = htmlspecialchars($p['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $img = (string) ($p['image'] ?? '');
            $srcset = (string) ($p['srcset'] ?? '');
            $teaser = (string) ($p['teaser_html'] ?? '');
            ?>
            <a href="<?= $url ?>" class="flex flex-col gap-20 md:gap-25 relative group p-3 pb-5 bg-blue-dark with-hover:bg-transparent hover:bg-blue-dark transition-colors duration-600">
                <div class="md:grid md:grid-cols-2 md:gap-5">
                    <div class="group-hover:[&_img]:scale-105 [&_img]:transition-transform [&_img]:duration-600 xl:col-start-2">
                        <?php if ($img !== '') { ?>
                            <div class="block relative pb-16/9 overflow-hidden">
                                <img width="1920" height="1080" src="<?= htmlspecialchars($img, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                     class="absolute image-cover wp-post-image" alt="<?= $title ?>" decoding="async"
                                     <?php if ($srcset !== '') { ?>srcset="<?= htmlspecialchars($srcset, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" sizes="(max-width: 1920px) 100vw, 1920px"<?php } ?> />
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="flex flex-col gap-5 items-start md:grid md:grid-cols-2 md:gap-5">
                    <div>
                        <h3 class="text-h2 text-justify"><?= $title ?></h3>
                        <?php if ($teaser !== '') { ?>
                            <div class="text-p2 mt-4"><?= $teaser ?></div>
                        <?php } ?>
                    </div>
                    <div class="flex md:justify-end md:pr-8">
                        <span class="text-p2 button-default gap-6">
                            <span>подробнее</span>
                            <span class="flex items-center -rotate-45 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-300">
                                <span class="w-3 border-b border-current"></span>
                                <svg class="w-[8px] h-[10px] fill-current">
                                    <use xlink:href="#arrow-tip"></use>
                                </svg>
                            </span>
                        </span>
                    </div>
                </div>
            </a>
        <?php } ?>

        <a href="/balet_na_baikale/" class="flex flex-col gap-20 md:gap-25 relative group p-3 pb-5 bg-blue-dark with-hover:bg-transparent hover:bg-blue-dark transition-colors duration-600">
            <div class="md:grid md:grid-cols-2 md:gap-5">
                <div class="group-hover:[&_img]:scale-105 [&_img]:transition-transform [&_img]:duration-600 xl:col-start-2">
                    <div class="block relative pb-16/9 overflow-hidden">
                        <img width="1024" height="666" src="https://markhakshinov.ru/opera/bg.jpg" class="absolute image-cover wp-post-image" alt="" decoding="async" fetchpriority="high" srcset="https://markhakshinov.ru/opera/bg.jpg 1024w, https://markhakshinov.ru/opera/bg.jpg 300w, https://markhakshinov.ru/opera/bg.jpg 768w, https://markhakshinov.ru/opera/bg.jpg 600w" sizes="(max-width: 1024px) 100vw, 1024px" />
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-5 items-start md:grid md:grid-cols-2 md:gap-5">
                <div>
                    <h3 class="text-h2 text-justify">«Балет на Байкале. Бурятия» пройдёт уже четвёртый год подряд. За это время программа расширилась и превратилась из небольшого концерта в международный фестиваль</h3>
                </div>
                <div class="flex md:justify-end md:pr-8">
                    <span class="text-p2 button-default gap-6">
                        <span>подробнее</span>
                        <span class="flex items-center -rotate-45 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform duration-300">
                            <span class="w-3 border-b border-current"></span>
                            <svg class="w-[8px] h-[10px] fill-current">
                                <use xlink:href="#arrow-tip"></use>
                            </svg>
                        </span>
                    </span>
                </div>
            </div>
        </a>
    </div>
</div>
