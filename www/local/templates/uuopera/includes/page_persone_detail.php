<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$slug = (string) ($GLOBALS['UUOPERA_PERSONE_SLUG'] ?? '');
$person = $slug !== '' ? uuopera_persone_by_slug($slug) : null;

if ($person === null) { ?>
    <main class="page-padding">
        <div class="wrapper-main wrapper-max pt-10">
            <h1 class="text-h1">Персона не найдена</h1>
            <p class="text-p2 mt-6 opacity-70">Код: <?= htmlspecialchars($slug, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></p>
            <p class="mt-4"><a href="/personalii/hudr/" class="text-p2 underline">← К списку</a></p>
        </div>
    </main>
<?php } else {
    $pname = htmlspecialchars($person['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $prole = htmlspecialchars($person['role'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $photo = $person['photo'];
    $personeAfishaEvents = uuopera_persone_afisha_events_for_slug($slug);
    ?>
<main class="page-padding flex flex-col gap-20 2xl:gap-25">

    <div class="page-padding pb-12 lg:pb-25 bg-beige overflow-hidden" data-header-color-schema="beige">
        <div class="wrapper-main wrapper-max w-full pt-3 lg:pt-25">
            <div class="relative">
                <div class="absolute top-[50%] left-[50%] -translate-x-[50%] -translate-y-[50%] mt-10 w-full px-15 hidden lg:block">
                    <div class="relative pb-[40.74%] opacity-50">
                        <svg class="absolute top-0 left-0 w-full h-full fill-white">
                            <use xlink:href="#logo"></use>
                        </svg>
                    </div>
                </div>
                <div class="flex flex-col gap-8 lg:grid lg:grid-cols-2 lg:gap-5 relative xl:min-h-[500px]">
                    <div class="lg:order-1 flex items-end">
                        <div class="relative pb-[135%] w-full max-w-sm overflow-hidden bg-white">
                            <?php if ($photo !== '') {
                                $photoSrc = htmlspecialchars($photo, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                ?>
                                <img src="<?= $photoSrc ?>"
                                     alt="<?= $pname ?>"
                                     class="absolute w-full h-[90%] left-0 bottom-0 object-contain wp-post-image"
                                     decoding="async" loading="eager" />
                            <?php } ?>
                        </div>
                    </div>
                    <div class="md:grid md:grid-cols-12 lg:grid-cols-6 md:gap-5">
                        <div class="flex flex-col justify-end gap-5 md:gap-8 md:col-span-10 lg:col-span-5 pb-8">
                            <?php if ($prole !== '') { ?>
                                <div class="text-p3 opacity-70"><?= $prole ?></div>
                            <?php } ?>
                            <h1 class="text-h1 text-balance"><?= $pname ?></h1>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (trim($person['detail_html']) !== '') { ?>
    <div class="wrapper-main wrapper-max w-full">
        <div class="flex flex-col gap-20 text-p2">
            <?= $person['detail_html'] ?>
        </div>
    </div>
    <?php } ?>

    <?php include __DIR__ . '/_persone_afisha_events.inc.php'; ?>

</main>
<?php } ?>
