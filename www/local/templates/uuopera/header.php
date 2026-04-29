<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */

$uuoperaTpl = SITE_TEMPLATE_PATH;

$uuoperaResolve = static function (string $html, string $tpl): string {
    $html = str_replace('__SPRITE_URL__', $tpl . '/assets/sprite.svg', $html);
    $html = str_replace('href="tpl/', 'href="' . $tpl . '/tpl/', $html);
    $html = str_replace("href='tpl/", "href='" . $tpl . '/tpl/', $html);
    $html = str_replace("src=\"tpl/", 'src="' . $tpl . '/tpl/', $html);
    $html = str_replace("src='tpl/", "src='" . $tpl . '/tpl/', $html);
    return $html;
};

$headAssets = $uuoperaResolve(
    (string) file_get_contents(__DIR__ . '/partials/_head_assets.html'),
    $uuoperaTpl
);
$bodyHeader = $uuoperaResolve(
    (string) file_get_contents(__DIR__ . '/partials/_body_header.html'),
    $uuoperaTpl
);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_megamenu.php';
$uuMegaMenuHtml = uuopera_megamenu_render_html();
if ($uuMegaMenuHtml === '') {
    $uuMegaFallback = __DIR__ . '/partials/_body_header_megamenu_fallback.html';
    $uuMegaMenuHtml = is_readable($uuMegaFallback) ? (string) file_get_contents($uuMegaFallback) : '';
}
$bodyHeader = str_replace('__UUOPERA_MEGAMENU__', $uuMegaMenuHtml, $bodyHeader);
?>
<!DOCTYPE html>
<html lang="<?= LANGUAGE_ID ?>">

<head>
    <?php $APPLICATION->ShowHead(); ?>
    <title><?php $APPLICATION->ShowTitle(); ?></title>
    <?= $headAssets ?>
    <?php
    $uuoperaExtraCss = $GLOBALS['UUOPERA_EXTRA_CSS'] ?? [];
    if (is_array($uuoperaExtraCss)) {
        foreach ($uuoperaExtraCss as $cssRel) {
            $href = $uuoperaTpl . '/' . ltrim((string) $cssRel, '/');
            $enc = defined('SITE_CHARSET') ? SITE_CHARSET : 'UTF-8';
            echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, $enc) . '" type="text/css" media="all" />' . "\n";
        }
    }
    ?>
</head>
<body>
<?php $APPLICATION->ShowPanel(); ?>
<?= $bodyHeader ?>
