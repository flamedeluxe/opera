<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var list<array{url: string, name: string, date_labels: list<string>, roles: list<string>, hero_image: string, hero_srcset: string}> $personeAfishaEvents */
if ($personeAfishaEvents === []) {
    return;
}
?>
<div class="flex flex-col gap-15 relative">
    <div class="py-15 bg-beige" data-header-color-schema="beige">
        <div class="wrapper-main wrapper-max w-full">
            <?php foreach ($personeAfishaEvents as $event) {
                $eventUrl = htmlspecialchars((string) ($event['url'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $eventName = htmlspecialchars((string) ($event['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $img = (string) ($event['hero_image'] ?? '');
                $srcset = (string) ($event['hero_srcset'] ?? '');
                $dateLabels = (array) ($event['date_labels'] ?? []);
                $roles = (array) ($event['roles'] ?? []);
                ?>
                <a href="<?= $eventUrl ?>" class="py-4 border-t flex flex-col gap-10 md:grid md:grid-cols-12 md:gap-5 group">
                    <?php if ($dateLabels !== []) { ?>
                        <div class="flex gap-x-6 gap-y-2 flex-wrap md:col-span-5 xl:col-span-4 self-start">
                            <?php foreach ($dateLabels as $dateLabel) { ?>
                                <div><?= htmlspecialchars((string) $dateLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <div class="flex flex-col gap-2 justify-between md:col-span-3 md:col-start-7">
                        <div class="text-2xl"><?= $eventName ?></div>
                        <?php if ($roles !== []) { ?>
                            <div class="text-p2 flex flex-col flex-wrap gap-2 md:pb-2">
                                <?php foreach ($roles as $role) { ?>
                                    <div><?= htmlspecialchars((string) $role, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                    <?php if ($img !== '') { ?>
                        <div class="md:col-span-3 hidden md:block">
                            <div class="relative pb-16/9 xl:pb-[50%] overflow-hidden group-hover:[&_img]:scale-105 [&_img]:transition-transform [&_img]:duration-600">
                                <img width="1920" height="1080"
                                     src="<?= htmlspecialchars($img, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                     class="absolute image-cover wp-post-image"
                                     alt="<?= $eventName ?>"
                                     decoding="async"
                                     <?php if ($srcset !== '') { ?>
                                         srcset="<?= htmlspecialchars($srcset, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                         sizes="(max-width: 1920px) 100vw, 1920px"
                                     <?php } ?> />
                            </div>
                        </div>
                    <?php } ?>
                </a>
            <?php } ?>
        </div>
    </div>
</div>
