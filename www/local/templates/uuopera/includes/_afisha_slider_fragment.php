<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array<string, mixed> $d */
/** @var string $sliderId */
$gallerySlides = $d['gallery'];
?>
    <div class="flex flex-col gap-12 md:gap-15 2xl:gap-25 wrapper-main wrapper-max w-full">
        <div class="lazyblock-image-slider-default-<?= htmlspecialcharsbx($sliderId) ?> wp-block-lazyblock-image-slider-default">
    <div class="xl:grid xl:grid-cols-12 xl:gap-5">
        <div class="xl:col-span-9 xl:col-start-4 -mx-3 md:-mx-6 xl:ml-0 xl:-mr-6 3xl:mr-0">
            <div class="swiper slider-default" data-slider-default="<?= htmlspecialcharsbx($sliderId) ?>">
                <div class="swiper-wrapper">
                        <?php foreach ($gallerySlides as $src): ?>
                                            <div class="swiper-slide" data-slide>
                            <div class="relative pb-16/9">
                                <img decoding="async" src="<?= htmlspecialcharsbx((string) $src) ?>" alt="" class="absolute image-cover" data-img>
                            </div>
                        </div>
                        <?php endforeach; ?>
                                    </div>
            </div>
            <div class="mt-3 sm:w-[92%] xl:w-[78%] px-3 md:px-6 xl:px-0 ">
                <div class="border-y border-y-current flex">
                                            <div class="whitespace-nowrap min-w-25 flex-grow sm:flex-grow-0 flex justify-center items-center sm:border-r sm:border-r-current" data-slider-pagination="<?= htmlspecialcharsbx($sliderId) ?>"></div>
                                        <div class="text-p2 hidden sm:flex items-center sm:flex-grow px-5 min-h-13" data-slider-description="<?= htmlspecialcharsbx($sliderId) ?>"></div>
                                            <div class="flex gap-10 h-13 items-center border-l border-l-current px-8">
                            <button class="flex items-center group py-3 disabled:opacity-60 disabled:pointer-events-none" data-slider-prev-button="<?= htmlspecialcharsbx($sliderId) ?>">
                                <svg class="w-[8px] h-[10px] fill-current rotate-180">
                                    <use xlink:href="#arrow-tip"></use>
                                </svg>
                                <span class="w-10 h-0.5 bg-current group-hover:scale-x-125 origin-left transition-transform duration-300"></span>
                            </button>
                            <button class="flex items-center group py-3 disabled:opacity-60 disabled:pointer-events-none" data-slider-next-button="<?= htmlspecialcharsbx($sliderId) ?>">
                                <span class="w-10 h-0.5 bg-current group-hover:scale-x-125 origin-right transition-transform duration-300"></span>
                                <svg class="w-[8px] h-[10px] fill-current">
                                    <use xlink:href="#arrow-tip"></use>
                                </svg>
                            </button>
                        </div>
                                    </div>
            </div>
        </div>
    </div>
</div>
	</div>
