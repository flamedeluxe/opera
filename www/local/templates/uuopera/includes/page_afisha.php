<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$afishaCats = [
    'opera' => 'Опера',
    'ballet' => 'Балет',
    'concert' => 'Концерт',
    'excursions' => 'Экскурсии',
    'festivals' => 'Фестивали',
    'online' => 'Онлайн',
    'performances' => 'Спектакли',
    'no-category' => 'Без категории',
    'abonement' => 'Абонемент',
    'musical' => 'Мюзикл',
];

$currentCat = (string) ($GLOBALS['UUOPERA_AFISHA_LIST_CATEGORY'] ?? '');
$events = uuopera_afisha_list_events($currentCat, 0, 'ASC', date('d.m.Y H:i:s'));
?>
<div class="flex flex-col gap-10 pt-32 wrapper-main wrapper-max" data-header-color-schema="beige">
    <h1 class="text-h1">Афиша</h1>

    <div class="flex flex-wrap gap-5 xl:gap-8 text-xxs tracking-widest uppercase">
        <a href="/afisha/" class="[&.active]:opacity-100 hover:opacity-100 opacity-60 transition-opacity duration-300<?= $currentCat === '' ? ' active' : '' ?>">Все</a>
        <?php foreach ($afishaCats as $slug => $label) { ?>
            <a href="/afisha/<?= htmlspecialchars($slug, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>/"
               class="[&.active]:opacity-100 hover:opacity-100 opacity-60 transition-opacity duration-300<?= $currentCat === $slug ? ' active' : '' ?>">
                <?= htmlspecialchars($label, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
            </a>
        <?php } ?>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-y-15 gap-x-5 2xl:gap-y-25">
        <?php
        foreach ($events as $card) {
            include __DIR__ . '/_afisha_list_card.php';
        }
        ?>
    </div>
</div>
