<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$sd = uuopera_services_get_data();
?>
<main class="page-padding wrapper-main wrapper-max w-full">
    <div class="flex flex-col gap-20 pt-20">
        <div class="flex flex-col gap-15">
            <h1 class="text-h1">Платные услуги</h1>
            <div class="flex flex-col gap-15 md:grid md:grid-cols-12 md:gap-x-5">
                <?php if ($sd['files'] !== []): ?>
                <div class="md:col-span-12 lg:col-span-6 flex flex-col gap-3 md:grid md:grid-cols-2 md:gap-5">
                    <?php foreach ($sd['files'] as $file): ?>
                    <div>
                        <a href="<?= htmlspecialchars($file['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                           target="_blank" rel="nofollow"
                           class="flex justify-between lg:justify-start gap-4 lg:gap-12 items-center group pb-3 border-b lg:border-none">
                            <span><?= $file['name'] ?></span>
                            <span class="group-hover:translate-y-px transition-transform duration-300">
                                <svg class="w-3 h-3.5 fill-current">
                                    <use xlink:href="#download"></use>
                                </svg>
                            </span>
                        </a>
                    </div>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
                <?php if (trim($sd['intro_html']) !== ''): ?>
                <div class="md:col-span-9 lg:col-span-6 xl:col-span-5 text-p1">
                    <?= $sd['intro_html'] ?>
                </div>
                <?php endif ?>
            </div>
        </div>

        <div class="lazyblock-services-Z1e4XIs wp-block-lazyblock-services">
            <div>
                <?php foreach ($sd['items'] as $item):
                    $iName    = htmlspecialchars($item['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $iPerson  = htmlspecialchars($item['contact_person'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $iPhone   = htmlspecialchars($item['phone'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $iEmail   = htmlspecialchars($item['email'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $iImg     = htmlspecialchars($item['image_url'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                ?>
                <div class="group" data-accordion>
                    <div class="text-p1 py-3 px-2 border-t flex gap-10 justify-between items-center cursor-pointer" data-accordion-toggle>
                        <span class="max-w-[80%] sm:max-w-[60%] lg:max-w-[50%] xl:max-w-[40%]"><?= $iName ?></span>
                        <span class="w-2 h-2 rounded-full bg-current group-[&.open]:opacity-40 shrink-0"></span>
                    </div>
                    <div class="hidden" data-accordion-content>
                        <div class="flex flex-col gap-10 lg:grid lg:grid-cols-2 lg:gap-5 px-2 pt-4 pb-12">
                            <div class="flex flex-col gap-6 justify-between">
                                <?php if (trim($item['description']) !== ''): ?>
                                <div class="md:grid md:grid-cols-6 md:gap-5">
                                    <div class="md:col-span-3 lg:col-span-4 xl:col-span-3 xl:text-base">
                                        <?= $item['description'] ?>
                                    </div>
                                </div>
                                <?php endif ?>
                                <div class="flex flex-col gap-2">
                                    <?php if ($iPerson !== ''): ?>
                                    <div><?= $iPerson ?></div>
                                    <?php endif ?>
                                    <div>
                                        <?php if ($iEmail !== ''): ?>
                                        <div><a href="mailto:<?= $iEmail ?>"><?= $iEmail ?></a></div>
                                        <?php endif ?>
                                        <?php if ($iPhone !== ''): ?>
                                        <div><a href="tel:<?= preg_replace('/[^\d+]/', '', $item['phone']) ?>"><?= $iPhone ?></a></div>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3 xl:gap-6 justify-between">
                                <?php if ($iImg !== ''): ?>
                                <div class="relative pb-[37%]">
                                    <img src="<?= $iImg ?>" alt="<?= $iName ?>" class="absolute image-cover">
                                </div>
                                <?php endif ?>
                                <?php if (trim($item['description_extra']) !== ''): ?>
                                <div class="md:grid md:grid-cols-2 md:gap-5">
                                    <div><?= $item['description_extra'] ?></div>
                                </div>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</main>
