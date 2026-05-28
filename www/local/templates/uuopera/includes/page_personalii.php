<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$currentCat   = (string) ($GLOBALS['UUOPERA_PERSONALII_CATEGORY'] ?? '');
$currentTitle = (string) ($GLOBALS['UUOPERA_PERSONALII_TITLE'] ?? 'Труппа');
/** @var array<string, string> $cats */
$cats = (array) ($GLOBALS['UUOPERA_PERSONALII_CATS'] ?? []);
/** @var list<string> $groupOrder */
$groupOrder = (array) ($GLOBALS['UUOPERA_PERSONALII_GROUP_ORDER'] ?? []);

$persons = uuopera_persone_list_by_category($currentCat);
$persons = uuopera_persone_filter_groups_for_category($currentCat, $persons, $groupOrder);
?>
<main class="page-padding" data-header-color-schema="beige">
    <div class="wrapper-max w-full pt-25">
        <div class="relative">

            <div class="absolute top-0 left-[50%] -translate-x-[50%] w-full px-15 -mt-8 hidden md:block">
                <div class="relative pb-[40.74%] opacity-50">
                    <svg class="absolute top-0 left-0 w-full h-full fill-white">
                        <use xlink:href="#logo"></use>
                    </svg>
                </div>
            </div>

            <div class="flex flex-col gap-15 relative">
                <div class="flex flex-col gap-12">
                    <h1 class="text-h1 wrapper-main"><?= htmlspecialchars($currentTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></h1>

                    <?php if (!empty($cats)): ?>
                    <div class="max-w-full overflow-auto no-scrollbar flex flex-wrap gap-5 xl:gap-8 py-4 wrapper-main">
                        <?php foreach ($cats as $slug => $label):
                            $isActive = ($slug === $currentCat);
                            $href = htmlspecialchars('/personalii/' . $slug . '/', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $labelHtml = htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            ?>
                            <?php if ($isActive): ?>
                                <span class="text-xxs uppercase tracking-widest link-hover whitespace-nowrap opacity-100"><?= $labelHtml ?></span>
                            <?php else: ?>
                                <a href="<?= $href ?>" class="text-xxs uppercase tracking-widest link-hover whitespace-nowrap opacity-40"><?= $labelHtml ?></a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="wrapper-main">
                    <?php if (empty($persons)): ?>
                        <div class="text-p2 opacity-60 pb-20">Данные загружаются. Запустите скрипт импорта персоналий.</div>
                    <?php else: ?>
                    <div class="flex flex-col gap-15 md:gap-20 pb-20">
                        <?php foreach ($persons as $groupName => $groupPersons): ?>
                        <div class="flex flex-col gap-6">
                            <h2 class="text-p1 uppercase"><?= htmlspecialchars((string) $groupName, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-x-5 gap-y-15">
                                <?php foreach ($groupPersons as $person):
                                    $personUrl  = htmlspecialchars('/persone/' . $person['slug'] . '/', ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    $personName = htmlspecialchars($person['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    $personRole = htmlspecialchars($person['role'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    $photo      = $person['photo'];
                                    ?>
                                    <a href="<?= $personUrl ?>" class="flex flex-col gap-3 relative group">
                                        <div class="group-hover:[&_img]:scale-105 [&_img]:transition-transform [&_img]:duration-600 link-stretching">
                                            <div class="block relative pb-[135%] overflow-hidden bg-white">
                                                <?php if ($photo !== ''): ?>
                                                    <img src="<?= htmlspecialchars($photo, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                                                         alt="<?= $personName ?>"
                                                         class="absolute w-full h-[90%] left-0 bottom-0 object-contain wp-post-image"
                                                         loading="lazy" decoding="async" />
                                                <?php else: ?>
                                                    <div class="absolute inset-0 flex items-end justify-center pb-2">
                                                        <svg class="w-[60%] h-[70%] opacity-10 fill-current">
                                                            <use xlink:href="#logo"></use>
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <h3 class="text-p2"><?= $personName ?></h3>
                                            <?php if ($personRole !== ''): ?>
                                                <div class="text-p3"><?= $personRole ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</main>
