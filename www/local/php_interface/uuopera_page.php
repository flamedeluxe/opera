<?php

declare(strict_types=1);

/**
 * @param array{
 *   title?: string,
 *   include: string,
 *   extra_css?: list<string>,
 *   footer_js?: list<string>,
 *   title_callback?: callable(\CMain): void|string
 * } $config
 */
function uuopera_page(array $config): void
{
    $doc = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    $include = (string) ($config['include'] ?? '');
    $title = (string) ($config['title'] ?? '');
    $titleCallback = $config['title_callback'] ?? null;
    $extraCss = $config['extra_css'] ?? [];
    $footerJs = $config['footer_js'] ?? [];

    if ($include === '' || !is_file($doc . $include)) {
        throw new InvalidArgumentException('uuopera_page: include file not found: ' . $include);
    }
    if (!is_array($extraCss)) {
        $extraCss = [];
    }
    if (!is_array($footerJs)) {
        $footerJs = [];
    }

    if (!is_file($doc . '/bitrix/header.php')) {
        if (is_file($doc . '/bitrixsetup.php')) {
            header('Location: /bitrixsetup.php', true, 302);
            exit;
        }
        http_response_code(503);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Битрикс не установлен. Откройте /bitrixsetup.php';
        exit;
    }

    $GLOBALS['UUOPERA_EXTRA_CSS'] = $extraCss;
    $GLOBALS['UUOPERA_FOOTER_JS'] = $footerJs;

    if (!defined('START_EXEC_PROLOG_BEFORE_2')) {
        require_once $doc . '/bitrix/modules/main/include/prolog_before.php';
    }
    /** @var CMain $APPLICATION */
    if ($titleCallback !== null && is_callable($titleCallback)) {
        $titleCallback($APPLICATION);
    } else {
        $APPLICATION->SetTitle($title);
        if ($title !== '') {
            $APPLICATION->SetPageProperty('title', $title);
        }
    }
    require $doc . '/bitrix/modules/main/include/prolog_after.php';

    require $doc . $include;

    require $doc . '/bitrix/footer.php';
}
