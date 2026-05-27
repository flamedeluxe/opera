<?php

declare(strict_types=1);

/**
 * ID инфоблока «Новости» (тип news из демо-установки обычно = 1).
 * Сменить: Настройки → Настройки продукта → Модули → main не используется;
 * проще — выполнить один раз в php консоли админки или временно:
 *   \Bitrix\Main\Config\Option::set('uuopera', 'news_iblock_id', '5');
 */
function uuopera_news_iblock_id(): int
{
    if (!class_exists(\Bitrix\Main\Config\Option::class)) {
        return 1;
    }
    $raw = (string) \Bitrix\Main\Config\Option::get('uuopera', 'news_iblock_id', '1');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 1;
}

/** @param \CMain $APPLICATION */
function uuopera_news_apply_detail_title($APPLICATION): void
{
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        $APPLICATION->SetTitle('Новость');
        return;
    }
    $iblockId = uuopera_news_iblock_id();
    $id = (int) ($GLOBALS['UUOPERA_NEWS_ELEMENT_ID'] ?? 0);
    if ($id > 0) {
        $res = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $id, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME']
        );
        if ($row = $res->GetNext()) {
            $name = (string) $row['NAME'];
            $APPLICATION->SetTitle($name);
            $APPLICATION->SetPageProperty('title', $name);
            return;
        }
    }
    $code = (string) ($GLOBALS['UUOPERA_NEWS_ELEMENT_CODE'] ?? '');
    if ($code !== '') {
        $res = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                '=CODE' => $code,
                'ACTIVE' => 'Y',
                'CHECK_PERMISSIONS' => 'Y',
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME']
        );
        if ($row = $res->GetNext()) {
            $name = (string) $row['NAME'];
            $APPLICATION->SetTitle($name);
            $APPLICATION->SetPageProperty('title', $name);
            return;
        }
    }
    $APPLICATION->SetTitle('Новость');
}

/**
 * Инфоблок «Мегаменю» (uuopera / uuopera_megamenu): разделы = колонки, элементы = пункты.
 * Создаётся скриптом /local/tools/uuopera_megamenu_iblock_install.php
 */
function uuopera_megamenu_iblock_id(): int
{
    if (!class_exists(\Bitrix\Main\Config\Option::class)) {
        return 0;
    }
    $raw = (string) \Bitrix\Main\Config\Option::get('uuopera', 'megamenu_iblock_id', '0');
    $digits = preg_replace('/\D/', '', $raw);
    return $digits !== '' ? (int) $digits : 0;
}

/**
 * Экскурсии: install + импорт (uuopera_excursions_iblock_install.php, uuopera_excursions_import_uuopera.php) → uuopera/excursions_iblock_id.
 */
require_once __DIR__ . '/uuopera_html.php';
require_once __DIR__ . '/uuopera_iblock_gallery.php';
require_once __DIR__ . '/uuopera_excursions.php';
require_once __DIR__ . '/uuopera_afisha_events.php';
require_once __DIR__ . '/uuopera_afisha_events_bootstrap.php';
require_once __DIR__ . '/uuopera_cms_data.php';

require_once __DIR__ . '/uuopera_afisha_admin.php';

AddEventHandler('main', 'OnProlog', 'uuopera_afisha_upgrade_pushkin_card_on_admin');

function uuopera_afisha_upgrade_pushkin_card_on_admin(): void
{
    if (!defined('ADMIN_SECTION') || ADMIN_SECTION !== true) {
        return;
    }
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return;
    }
    $iblockId = uuopera_afisha_events_iblock_id();
    if ($iblockId > 0) {
        uuopera_afisha_events_upgrade_pushkin_card_checkbox($iblockId);
    }
}

AddEventHandler('main', 'OnAdminIBlockElementEdit', 'uuopera_afisha_admin_tab_engine_handler');
AddEventHandler('main', 'OnEpilog', 'uuopera_afisha_admin_hide_technical_props_epilog');
