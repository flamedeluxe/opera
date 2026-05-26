<?php
declare(strict_types=1);
/**
 * Обновляет DETAIL_TEXT проектов контентом, извлечённым с продакшна.
 */
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

$iblockId = (int)\Bitrix\Main\Config\Option::get('uuopera', 'cms_projects_iblock_id', '0');
echo "iblock ID: $iblockId\n";

$contents = [
    'opera100' => <<<'HTML'
<div class="lazyblock-two-columns wp-block-lazyblock-two-columns">
<div class="uuopera-two-columns">
<div class="wp-block-columns is-layout-flex wp-block-columns-is-layout-flex">
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><div class="wp-block-lazyblock-text">
<div class="flex flex-col gap-[1em] text-large">
<p>Министерство культуры Республики Бурятия и Бурятский театр оперы и балета представляют открытый конкурс на создание национального оперного спектакля в рамках подготовки и проведения празднования 100-летия образования Республики Бурятия.</p>
</div>
</div></div>
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><div class="wp-block-lazyblock-text">
<div class="flex flex-col gap-[1em] text-medium">
<p>Главная цель проекта — к знаменательной дате поставить оперный спектакль, сопоставимый по значению с легендарной бурятской оперой — «Энхэ-Булат батор», которая, как известно, является первым произведением профессионального искусства Бурятии и частью культурного наследия бурятского народа, созданного в конце 30-х годов прошлого столетия. Автором музыки к опере стал уральский композитор Маркиан Фролов, а автором либретто по мотивам бурятского улигера о Шоно-баторе — драматург, актер и режиссер Намжил Балдано.</p>
<p>В конкурсе в качестве либретто-претендентов допускаются произведения, основанные на событии из истории бурятского народа, биографии выдающихся исторических личностей или бурятском фольклоре (сказка, легенда, эпос).</p>
<p>В декабре 2020 года для участников будет проведен мастер-класс «Особенности оперного либретто».</p>
</div>
</div></div>
</div>
</div></div>
HTML,

    'konkbalet100' => <<<'HTML'
<div class="lazyblock-two-columns wp-block-lazyblock-two-columns">
<div class="uuopera-two-columns">
<div class="wp-block-columns uuopera-two-columns is-layout-flex wp-block-columns-is-layout-flex">
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><div class="wp-block-lazyblock-text">
<div class="flex flex-col gap-[1em] text-large">
<p>Министерство культуры Республики Бурятия и Бурятский театр оперы и балета представляют открытый конкурс на создание и постановку национального балетного спектакля в рамках подготовки и проведения празднования 100-летия образования Республики Бурятия.</p>
</div>
</div></div>
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><div class="wp-block-lazyblock-text">
<div class="flex flex-col gap-[1em] text-medium">
<p>Главная цель проекта — к знаменательной дате поставить балетный спектакль, сопоставимый по значению с легендарным бурятским балетом «Красавица Ангара», который, как известно, является первым балетом на бурятскую тематику и частью культурного наследия бурятского народа, созданного в 60-е годы прошлого столетия. Авторами музыки к балету стали бурятские композиторы Льва Книппер и Баудо Ямпилов, а автором либретто — бурятский драматург Намжил Балдано.</p>
<p>В конкурсе могут принять участие профессиональные хореографы, балетмейстеры и композиторы.</p>
</div>
</div></div>
</div>
</div></div>
HTML,

    's-16-po-19-aprelja-sostoitsja-vii-mezhdunarodnyj-konkurs-molodyh-opernyh-pevcov-imeni-narodnogo-artista-sssr-kima-bazarsadaeva' => <<<'HTML'
<div class="wp-block-lazyblock-text">
<div class="flex flex-col gap-[1em] text-extra-large">
<p>С 16 по 19 апреля состоится VII Международный конкурс молодых оперных певцов имени народного артиста СССР Кима Базарсадаева. Приглашаем всех желающих посетить туры конкурса и торжественный гала-концерт лауреатов.</p>
</div>
</div>
HTML,

    'polozhenie-o-provedenii-proekta-ambassadory-pushkinskoj-karty' => <<<'HTML'
<div class="wp-block-lazyblock-text">
<div class="flex flex-col gap-[1em] text-extra-large">
<p><a href="/wp-content/uploads/2025/02/polozhenie-ambassadory-pushkinskoy-karty.pdf">Скачать положение о проведении проекта «Амбассадоры Пушкинской карты»</a></p>
</div>
</div>
HTML,

    'spasjom-zhizn-vmeste-vserossijskij-konkurs-socialnoj-reklamy-antinarkoticheskoj-napravlennosti-i-propagandy-zdorovogo-obraza-zhizni' => <<<'HTML'
<div class="lazyblock-two-columns wp-block-lazyblock-two-columns">
<div class="uuopera-two-columns">
<div class="wp-block-columns is-layout-flex wp-block-columns-is-layout-flex">
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><div class="wp-block-lazyblock-text">
<div class="flex flex-col gap-[1em] text-large">
<p>Министерством внутренних дел Российской Федерации проводится Всероссийский конкурс социальной рекламы антинаркотической направленности и пропаганды здорового образа жизни «Спасём жизнь вместе!»</p>
</div>
</div></div>
<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><div class="wp-block-lazyblock-text">
<div class="flex flex-col gap-[1em] text-medium">
<p>Конкурс проводится среди учащихся общеобразовательных организаций, образовательных организаций среднего профессионального образования, высших учебных заведений и неработающей молодёжи в возрасте до 35 лет.</p>
<p>К участию принимаются работы в следующих номинациях: социальный плакат, видеоролик (продолжительностью от 30 секунд до 3 минут), анимационный ролик.</p>
<p>Бурятский театр оперы и балета приглашает всех желающих принять участие в данном конкурсе.</p>
</div>
</div></div>
</div>
</div></div>
HTML,
];

$el = new CIBlockElement();

foreach ($contents as $code => $html) {
    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'CODE' => $code],
        false,
        ['nTopCount' => 1],
        ['ID']
    );
    $row = $res->Fetch();
    if (!$row) {
        echo "NOT FOUND: $code\n";
        continue;
    }
    $eid = (int) $row['ID'];
    $ok = $el->Update($eid, [
        'DETAIL_TEXT'      => $html,
        'DETAIL_TEXT_TYPE' => 'html',
    ]);
    echo ($ok ? "OK" : "ERR") . " ID=$eid: $code\n";
}

echo "Готово.\n";
