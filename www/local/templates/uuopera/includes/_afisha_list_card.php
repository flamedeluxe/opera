<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
/** @var array<string, mixed> $card */
$alt = htmlspecialchars((string) ($card['name'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
$url = (string) ($card['url'] ?? '#');
$href = htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$img = (string) ($card['hero_image'] ?? '');
$srcset = (string) ($card['hero_srcset'] ?? '');
$sessionsLine = (string) ($card['sessions_line'] ?? '');
$teaserHtml = (string) ($card['teaser_html'] ?? '');
$age = trim((string) ($card['age'] ?? ''));
$pushkin = !empty($card['pushkin_card']);
$radKey = (string) ($card['radario_afisha_key'] ?? '');
$radEventId = (int) ($card['radario_event_id'] ?? 0);
?>
<div class="flex flex-col gap-5 relative group">
    <a href="<?= $href ?>" class="group-hover:[&_img]:scale-105 [&_img]:transition-transform [&_img]:duration-600 link-stretching">
        <div class="block relative pb-16/9 overflow-hidden">
            <?php if ($img !== '') { ?>
                <img width="1920" height="1080" src="<?= htmlspecialchars($img, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
                     class="absolute image-cover wp-post-image" alt="<?= $alt ?>" decoding="async"
                     <?php if ($srcset !== '') { ?>srcset="<?= htmlspecialchars($srcset, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>" sizes="(max-width: 1920px) 100vw, 1920px"<?php } ?> />
            <?php } ?>
        </div>
    </a>
    <div class="flex flex-col gap-5 text-xs uppercase">
        <?php if ($sessionsLine !== '') { ?>
            <div class="whitespace-nowrap w-full overflow-hidden text-ellipsis"><?= $sessionsLine ?></div>
        <?php } ?>
        <div class="flex justify-between gap-10">
            <h3><?= $alt ?></h3>
            <div class="whitespace-nowrap relative z-1">
                <style>
                    .radario-button__main {
                        font-size: 12px !important;
                        font-weight: 400 !important;
                    }
                </style>
                <?php
                if ($radEventId > 0) {
                    $payload = [
                        'params' => [
                            'accentColor' => 'rgba(30, 21, 18, 1)',
                            'textBtnColor' => '#FFFFFF',
                            'textColor' => '#3D3634',
                            'backgroundColor' => 'rgba(245, 239, 235, 1)',
                        ],
                        'buttonText' => 'купить билет',
                        'buttonPadding' => '8px 0px',
                        'buttonBorderRadius' => '0',
                        'standalone' => false,
                        'createButton' => true,
                        'eventId' => (string) $radEventId,
                    ];
                    echo '<script>radario.Widgets.Event(' . json_encode($payload, JSON_UNESCAPED_UNICODE) . ');</script>';
                } elseif ($radKey !== '') {
                    $payload = [
                        'params' => [
                            'accentColor' => 'rgba(30, 21, 18, 1)',
                            'textBtnColor' => '#FFFFFF',
                            'textColor' => '#3D3634',
                            'backgroundColor' => 'rgba(245, 239, 235, 1)',
                        ],
                        'buttonText' => 'купить билет',
                        'buttonPadding' => '8px 2px',
                        'buttonBorderRadius' => '0',
                        'standalone' => false,
                        'createButton' => true,
                        'key' => $radKey,
                    ];
                    echo '<script>radario.Widgets.Afisha(' . json_encode($payload, JSON_UNESCAPED_UNICODE) . ');</script>';
                } elseif ($url !== '' && $url !== '#') {
                    ?>
                    <a href="<?= $href ?>" class="whitespace-nowrap relative z-1">купить билет</a>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="flex justify-between items-start gap-10">
            <div>
                <?= $teaserHtml ?>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($age !== '') { ?>
                    <div><?= htmlspecialchars($age, ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></div>
                <?php } ?>
                <?php if ($pushkin) { ?>
                    <div>
                        <svg class="w-[17px] h-[16px] stroke-none fill-current">
                            <use xlink:href="#pushkin"></use>
                        </svg>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
