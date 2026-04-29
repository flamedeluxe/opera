<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$uuoperaTpl = SITE_TEMPLATE_PATH;
$html = (string) file_get_contents(__DIR__ . '/partials/_footer.html');

$pageScripts = '';
$jsList = $GLOBALS['UUOPERA_FOOTER_JS'] ?? [];
if (is_array($jsList)) {
    foreach ($jsList as $jsRel) {
        $src = $uuoperaTpl . '/' . ltrim((string) $jsRel, '/');
        $pageScripts .= '<script type="text/javascript" src="'
            . htmlspecialchars($src, ENT_QUOTES | ENT_HTML5, defined('SITE_CHARSET') ? SITE_CHARSET : 'UTF-8')
            . '"></script>' . "\n";
    }
}
$html = str_replace('__UUOPERA_PAGE_SCRIPTS__', $pageScripts, $html);
$html = str_replace('href="tpl/', 'href="' . $uuoperaTpl . '/tpl/', $html);
$html = str_replace("href='tpl/", "href='" . $uuoperaTpl . '/tpl/', $html);
$html = str_replace('src="tpl/', 'src="' . $uuoperaTpl . '/tpl/', $html);
$html = str_replace("src='tpl/", "src='" . $uuoperaTpl . '/tpl/', $html);
echo $html;
