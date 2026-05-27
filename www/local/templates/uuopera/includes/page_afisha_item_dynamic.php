<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$code = (string) ($GLOBALS['UUOPERA_AFISHA_CODE'] ?? '');
$d = uuopera_afisha_event_get_data($code);

global $USER;
$uuoperaAfishaIsAdmin = is_object($USER) && $USER->IsAdmin();

if ($d['name'] === '') {
    ?>
<main class="flex flex-col gap-15">
    <div class="wrapper-main wrapper-max py-20 md:py-28 text-p2">
        <h1 class="text-h1 mb-6">Событие афиши</h1>
        <p class="max-w-2xl">Материал не найден в инфоблоке «События афиши». Импорт: <code class="text-p3">php local/tools/uuopera_afisha_bulk_import_uuopera.php</code></p>
        <?php if ($uuoperaAfishaIsAdmin): ?>
            <p class="mt-6 opacity-80">Символьный код: <code class="text-p3"><?= htmlspecialcharsbx($code) ?></code></p>
        <?php endif; ?>
    </div>
</main>
    <?php
    return;
}

$layout = $d['layout'] === 'excursion' ? 'excursion' : 'event';
$sliderId = (string) $d['slider_id'];
$hasGallery = $d['gallery'] !== [];
$radarioKeyJs = $d['radario_afisha_key'] !== ''
    ? json_encode($d['radario_afisha_key'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS)
    : '';
$heroSrcset = (string) $d['hero_srcset'];
$heroImage = (string) $d['hero_image'];

if ($layout === 'excursion') {
    ?>
<main class="flex flex-col gap-15">
    <div>
        <div class="relative" data-header-color-schema="transparent">
            <img width="1920" height="1080" src="<?= htmlspecialcharsbx($heroImage) ?>" class="absolute w-full h-full image-cover wp-post-image" alt="" decoding="async" loading="lazy"<?= $heroSrcset !== '' ? ' srcset="' . htmlspecialcharsbx($heroSrcset) . '"' : '' ?> sizes="auto, (max-width: 1920px) 100vw, 1920px" />
            <div class="absolute inset-0 bg-black-40"></div>
            <div class="flex portrait:min-h-[133vw] md:portrait:min-h-[100vw] min-h-[50vw]">
                <div class="relative flex-grow text-white min-h-[420px] md:min-h-[600px] wrapper-main wrapper-max flex items-end pb-12 pt-20 md:pb-16 md:portrait:pb-25">
                    <div class="flex flex-col gap-5 w-full">
                        <div class="flex gap-5 justify-between">
                            <div class="max-w-[600px]">
                                <h1 class="text-h1"><?= htmlspecialcharsbx((string) $d['name']) ?></h1>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-5 sm:justify-between sm:items-end text-h2">
                            <?php if (trim((string) $d['hero_meta_html']) !== ''): ?>
                            <div class="max-w-[400px]"><?= $d['hero_meta_html'] ?></div>
                            <?php endif; ?>
<?php if ($d['radario_hero_mode'] === 'afisha' && $d['radario_afisha_key'] !== ''): ?>
                <div>
                <style>.radario-button__main { font-size: 12px !important; font-weight: 400 !important; }</style>
                <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"купить билет","buttonPadding":"8px 2px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key": <?= $radarioKeyJs ?>});</script>
                </div>
<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    <div class="grid gap-5 xl:grid-cols-2 wrapper-main wrapper-max">
                                                    <div class="sm:grid sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-3 sm:gap-4 xl:gap-0.5 xl:col-start-2">
                        <?php foreach ($d['sessions'] as $sessionRow): ?>
                            <?php
                            $dateLabel = (string) ($sessionRow[0] ?? '');
                            $eventId = (int) ($sessionRow[1] ?? 0);
                            if ($dateLabel === '' || $eventId <= 0) {
                                continue;
                            }
                            ?>
                            <div class="group py-4 px-6 md:px-4 xl:px-8 button-default text-p3 border-b border-current with-hover:border-beige hover:border-current">
                                <span class="flex justify-between gap-2.5 w-full">
                                    <span style="white-space:nowrap; margin-right:5px"><?= htmlspecialcharsbx($dateLabel) ?></span>
                                </span>
<script style="with-hover:opacity-0 group-hover:opacity-100 transition-opacity duration-300">radario.Widgets.Event({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"Билеты","buttonPadding":"8px 0px","buttonBorderRadius":"0","standalone":false,"createButton":true,"eventId":<?= $eventId ?>});
</script>
</div>
                        <?php endforeach; ?>
                                            </div>
                            </div>
            </div>
    <div class="flex flex-col gap-15 md:grid md:grid-cols-12 md:gap-x-5 wrapper-main wrapper-max w-full">
                    <div class="flex flex-col gap-8 justify-between md:grid md:grid-cols-7 lg:grid-cols-6 xl:grid-cols-5 md:col-span-7 lg:col-span-6 lg:gap-x-5 xl:col-span-5 lg:col-start-7 xl:col-start-7 text-p2">
                <div class="md:col-span-6 lg:col-span-5 xl:col-span-3 flex flex-col gap-[1em]">
                    <?= $d['description_html'] ?>
                    <?php if ($d['footer_duration'] !== ''): ?><p><?= htmlspecialcharsbx((string) $d['footer_duration']) ?></p><?php endif; ?>
                    <?php if ($d['footer_price'] !== ''): ?><p><?= htmlspecialcharsbx((string) $d['footer_price']) ?></p><?php endif; ?>
                </div>
            </div>
            </div>
    <?php
    if ($hasGallery) {
        include __DIR__ . '/_afisha_slider_fragment.php';
    }
    ?>
</main>
    <?php
    return;
}

/* --- layout event (балет, опера, концерт) --- */
?>
<main class="flex flex-col gap-15">
    <div>
        <div class="relative" data-header-color-schema="transparent">
            <img width="1920" height="1080" src="<?= htmlspecialcharsbx($heroImage) ?>" class="absolute w-full h-full image-cover wp-post-image" alt="" decoding="async" loading="lazy"<?= $heroSrcset !== '' ? ' srcset="' . htmlspecialcharsbx($heroSrcset) . '"' : '' ?> sizes="auto, (max-width: 1920px) 100vw, 1920px" />
            <div class="absolute inset-0 bg-black-40"></div>
            <div class="flex portrait:min-h-[133vw] md:portrait:min-h-[100vw] min-h-[50vw]">
                <div class="relative flex-grow text-white min-h-[420px] md:min-h-[600px] wrapper-main wrapper-max flex items-end pb-12 pt-20 md:pb-16 md:portrait:pb-25">
                    <div class="flex flex-col gap-5 w-full">
                        <div class="flex gap-5 justify-between">
                            <div class="max-w-[600px]">
                                <h1 class="text-h1"><?= htmlspecialcharsbx((string) $d['name']) ?></h1>
                            </div>
                            <?php if (trim((string) $d['age']) !== ''): ?>
                            <div class="text-h2 pt-1"><?= htmlspecialcharsbx((string) $d['age']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-5 sm:justify-between sm:items-end text-h2">
                            <?php if (trim((string) $d['hero_meta_html']) !== ''): ?>
                            <div class="max-w-[400px]"><?= $d['hero_meta_html'] ?></div>
                            <?php endif; ?>
<?php
$showHeroRadario = ($d['radario_hero_mode'] === 'afisha' && $d['radario_afisha_key'] !== '')
    || ($d['radario_hero_mode'] === 'event' && (int) $d['radario_hero_event_id'] > 0);
?>
<?php if ($showHeroRadario): ?>
                <div>
                <style>.radario-button__main { font-size: 12px !important; font-weight: 400 !important; }</style>
<?php if ($d['radario_hero_mode'] === 'afisha' && $d['radario_afisha_key'] !== ''): ?>
                <script>radario.Widgets.Afisha({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"купить билет","buttonPadding":"8px 2px","buttonBorderRadius":"0","standalone":false,"createButton":true,"key": <?= $radarioKeyJs ?>});</script>
<?php else: ?>
                <script>radario.Widgets.Event({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"купить билет","buttonPadding":"8px 0px","buttonBorderRadius":"0","standalone":false,"createButton":true,"eventId":<?= (int) $d['radario_hero_event_id'] ?>});</script>
<?php endif; ?>
                </div>
<?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                    <div class="grid gap-5 xl:grid-cols-2 wrapper-main wrapper-max">
                                                    <div class="sm:grid sm:grid-cols-2 md:grid-cols-4 xl:grid-cols-3 sm:gap-4 xl:gap-0.5 xl:col-start-2">
                        <?php foreach ($d['sessions'] as $sessionRow): ?>
                            <?php
                            $dateLabel = (string) ($sessionRow[0] ?? '');
                            $eventId = (int) ($sessionRow[1] ?? 0);
                            if ($dateLabel === '' || $eventId <= 0) {
                                continue;
                            }
                            ?>
                            <div class="group py-4 px-6 md:px-4 xl:px-8 button-default text-p3 border-b border-current with-hover:border-beige hover:border-current">
                                <span class="flex justify-between gap-2.5 w-full">
                                    <span style="white-space:nowrap; margin-right:5px"><?= htmlspecialcharsbx($dateLabel) ?></span>
                                </span>
<script style="with-hover:opacity-0 group-hover:opacity-100 transition-opacity duration-300">radario.Widgets.Event({"params":{"accentColor":"rgba(30, 21, 18, 1)","textBtnColor":"#FFFFFF","textColor":"#3D3634","backgroundColor":"rgba(245, 239, 235, 1)"},"buttonText":"Билеты","buttonPadding":"8px 0px","buttonBorderRadius":"0","standalone":false,"createButton":true,"eventId":<?= $eventId ?>});
</script>
</div>
                        <?php endforeach; ?>
                                            </div>
                            </div>
            </div>

    <div class="flex flex-col gap-15 md:grid md:grid-cols-12 md:gap-x-5 wrapper-main wrapper-max w-full">
        <?php
        $participantsJson = is_array($d['participants_json'] ?? null) ? $d['participants_json'] : [];
        $participantsHtml = trim((string) ($d['participants_html'] ?? ''));
        if ($participantsJson !== []):
            $firstCastHtml = reset($participantsJson);
            $multiDate = count($participantsJson) > 1;
        ?>
                    <div class="flex flex-col gap-8 justify-between md:grid md:grid-cols-7 lg:grid-cols-6 xl:grid-cols-5 md:col-span-7 lg:col-span-6 lg:gap-x-5 xl:col-span-5 text-p2">
                <div class="flex flex-col gap-6 md:col-span-6 xl:col-span-4">
                    <div class="grid grid-cols-2 gap-5">
                        <div class="text-h2">Состав</div>
                        <?php if ($multiDate): ?>
                        <div class="flex gap-2 items-center">
                            <div class="spinner w-4 h-4 border-2 hidden [&.loading]:block" data-particiants-spinner></div>
                            <select name="date" class="outline-none cursor-pointer" data-code="<?= htmlspecialcharsbx($code) ?>" data-particiants-date-select>
                                <?php foreach (array_keys($participantsJson) as $sqlDt): ?>
                                    <?php
                                    $ts = strtotime($sqlDt);
                                    static $monthNames = [1=>'января',2=>'февраля',3=>'марта',4=>'апреля',5=>'мая',6=>'июня',7=>'июля',8=>'августа',9=>'сентября',10=>'октября',11=>'ноября',12=>'декабря'];
                                    $label = $ts ? ((int)date('j',$ts) . ' ' . ($monthNames[(int)date('n',$ts)] ?? '') . ' ' . date('H:i',$ts)) : $sqlDt;
                                    ?>
                                    <option value="<?= htmlspecialcharsbx($sqlDt) ?>"><?= htmlspecialcharsbx($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-col gap-3" data-particiants-container>
                        <?= $firstCastHtml ?>
                    </div>
                </div>
                    </div>
        <?php elseif ($participantsHtml !== ''): ?>
                    <div class="flex flex-col gap-8 justify-between md:grid md:grid-cols-7 lg:grid-cols-6 xl:grid-cols-5 md:col-span-7 lg:col-span-6 lg:gap-x-5 xl:col-span-5 text-p2">
                <?= $participantsHtml ?>
                    </div>
        <?php endif; ?>
                    <div class="flex flex-col gap-8 justify-between md:grid md:grid-cols-7 lg:grid-cols-6 xl:grid-cols-5 md:col-span-7 lg:col-span-6 lg:gap-x-5 xl:col-span-5 lg:col-start-7 xl:col-start-7 text-p2">
                <div class="md:col-span-6 lg:col-span-5 xl:col-span-3 flex flex-col gap-[1em]">
                    <?= $d['description_html'] ?>
                </div>
            </div>
            </div>

    <?php if (trim((string) $d['content_html']) !== ''): ?>
    <div class="flex flex-col gap-12 md:gap-15 2xl:gap-25 wrapper-main wrapper-max w-full">
        <?= $d['content_html'] ?>
    </div>
    <?php endif; ?>

    <?php if ($hasGallery) {
        include __DIR__ . '/_afisha_slider_fragment.php';
    } ?>

    <?php if ($uuoperaAfishaIsAdmin && (int) ($d['element_id'] ?? 0) > 0): ?>
    <div class="wrapper-main wrapper-max w-full">
        <div style="display:flex;gap:12px;align-items:center;padding:10px 14px;background:#f5f0eb;border:1px solid #ddd;font-size:13px;border-radius:2px">
            <span style="opacity:.6">Панель администратора:</span>
            <a href="/local/admin/afisha_sessions_edit.php?id=<?= (int) $d['element_id'] ?>" target="_blank"
               style="color:#5b7fbe;text-decoration:none;font-weight:500">✎ Сеансы и состав</a>
            <a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=<?= $iblockId ?? uuopera_afisha_events_iblock_id() ?>&type=uuopera&ID=<?= (int) $d['element_id'] ?>&lang=ru" target="_blank"
               style="color:#5b7fbe;text-decoration:none">Открыть в Bitrix ↗</a>
        </div>
    </div>
    <?php endif; ?>
</main>
