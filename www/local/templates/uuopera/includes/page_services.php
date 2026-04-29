<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$sd = uuopera_services_get_data();
$faq = uuopera_service_faq_list();
$pdf1 = htmlspecialchars($sd['pdf_regulation_url'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
$pdf2 = htmlspecialchars($sd['pdf_price_url'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
?>
<main class="page-padding wrapper-main wrapper-max w-full">
    <div class="flex flex-col gap-20 pt-20">
        <div class="flex flex-col gap-15">
            <h1 class="text-h1">Платные услуги</h1>
            <div class="flex flex-col gap-15 md:grid md:grid-cols-12 md:gap-x-5">
                <div class="md:col-span-12 lg:col-span-6 flex flex-col gap-3 md:grid md:grid-cols-2 md:gap-5">
                    <div>
                        <a href="<?= $pdf1 ?>" target="_blank" rel="nofollow" class="flex justify-between lg:justify-start gap-4 lg:gap-12 items-center group pb-3 border-b lg:border-none">
                            <span>Скачать положение<br>о платных услугах</span>
                            <span class="group-hover:translate-y-px transition-transform duration-300">
                                <svg class="w-3 h-3.5 fill-current">
                                    <use xlink:href="#download"></use>
                                </svg>
                            </span>
                        </a>
                    </div>
                    <div>
                        <a href="<?= $pdf2 ?>" target="_blank" rel="nofollow" class="flex justify-between lg:justify-start gap-4 lg:gap-12 items-center group pb-3 border-b lg:border-none">
                            <span>Скачать прейскурант<br>цен и услуг PDF</span>
                            <span class="group-hover:translate-y-px transition-transform duration-300">
                                <svg class="w-3 h-3.5 fill-current">
                                    <use xlink:href="#download"></use>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>
                <div class="md:col-span-9 lg:col-span-6 xl:col-span-5 text-p1">
                    <?= $sd['intro_html'] ?>
                </div>
            </div>
        </div>
        <div class="lazyblock-services-Z1e4XIs wp-block-lazyblock-services">
            <div>
                <?php if ($faq === []) { ?>
                    <p class="text-p2">Добавьте вопросы в инфоблок «Платные услуги: вопросы» (ответ — свойство ANSWER_HTML).</p>
                <?php } else {
                    foreach ($faq as $item) {
                        $q = htmlspecialchars($item['question'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        ?>
                        <div class="group" data-accordion>
                            <div class="text-p1 py-3 px-2 border-t flex gap-10 justify-between items-center cursor-pointer" data-accordion-toggle>
                                <span class="max-w-[80%] sm:max-w-[60%] lg:max-w-[50%] xl:max-w-[40%]"><?= $q ?></span>
                                <span class="w-2 h-2 rounded-full bg-current group-[&.open]:opacity-40 shrink-0"></span>
                            </div>
                            <div class="hidden" data-accordion-content>
                                <div class="flex flex-col gap-10 px-2 pt-4 pb-12 text-p2">
                                    <?= $item['answer_html'] ?>
                                </div>
                            </div>
                        </div>
                    <?php }
                } ?>
            </div>
        </div>
    </div>
</main>
