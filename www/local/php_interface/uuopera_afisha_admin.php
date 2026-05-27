<?php

declare(strict_types=1);

function uuopera_afisha_admin_embedded(): bool
{
    return (string) ($_REQUEST['embedded'] ?? '') === '1';
}

function uuopera_afisha_admin_read_prop(int $iblockId, int $elementId, string $code): string
{
    $res = CIBlockElement::GetProperty($iblockId, $elementId, ['sort' => 'asc'], ['CODE' => $code]);
    if ($row = $res->Fetch()) {
        $v = $row['VALUE'];
        return is_array($v) ? (string) ($v['TEXT'] ?? ($v[0] ?? '')) : (string) $v;
    }
    return '';
}

/**
 * @return list<array{label: string, event_id: int, sql_dt: string}>
 */
function uuopera_afisha_admin_parse_sessions(string $json): array
{
    $json = trim($json);
    if ($json === '') {
        return [];
    }
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return [];
    }
    $out = [];
    foreach ($decoded as $row) {
        if (!is_array($row) || count($row) < 1) {
            continue;
        }
        $out[] = [
            'label' => trim((string) ($row[0] ?? '')),
            'event_id' => (int) ($row[1] ?? 0),
            'sql_dt' => trim((string) ($row[2] ?? '')),
        ];
    }
    return $out;
}

/**
 * @param list<array{label: string, event_id: int, sql_dt: string}> $rows
 */
function uuopera_afisha_admin_build_sessions_json(array $rows): string
{
    $encoded = [];
    foreach ($rows as $row) {
        $label = trim((string) ($row['label'] ?? ''));
        $eventId = (int) ($row['event_id'] ?? 0);
        $sqlDt = trim((string) ($row['sql_dt'] ?? ''));
        if ($label === '' && $eventId <= 0) {
            continue;
        }
        if ($sqlDt !== '') {
            $encoded[] = [$label, $eventId, $sqlDt];
        } else {
            $encoded[] = [$label, $eventId];
        }
    }
    return $encoded === [] ? '' : json_encode($encoded, JSON_UNESCAPED_UNICODE);
}

/**
 * @return list<array{role: string, name: string, url: string}>
 */
function uuopera_afisha_admin_cast_parse_html(string $html): array
{
    $entries = [];
    $chunks = preg_split('/(?=<div class="grid grid-cols-2)/u', $html);
    foreach ($chunks as $chunk) {
        if (!str_contains($chunk, 'grid grid-cols-2')) {
            continue;
        }
        if (!preg_match(
            '/<div class="grid grid-cols-2[^"]*">\s*<div[^>]*>(.*?)<\/div>\s*(.*)$/us',
            trim($chunk),
            $row
        )) {
            continue;
        }
        $role = trim(strip_tags($row[1]));
        $block = preg_replace('/\s*<\/div>\s*<\/div>\s*$/u', '', trim($row[2]));
        $url = '';
        $name = '';
        if (preg_match('/<a[^>]+href="([^"]+)"/u', $block, $um)) {
            $url = html_entity_decode($um[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        if (preg_match('/<span[^>]*>([\s\S]*?)<\/span>/u', $block, $nm)) {
            $name = trim(html_entity_decode(strip_tags($nm[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        } else {
            $name = trim(html_entity_decode(strip_tags($block), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        if ($role !== '' || $name !== '') {
            $entries[] = ['role' => $role, 'name' => $name, 'url' => $url];
        }
    }
    return $entries;
}

/**
 * @param list<array{role: string, name: string, url: string}> $entries
 */
function uuopera_afisha_admin_cast_build_html(array $entries): string
{
    $html = '';
    foreach ($entries as $e) {
        $role = trim((string) ($e['role'] ?? ''));
        $name = trim((string) ($e['name'] ?? ''));
        $url = trim((string) ($e['url'] ?? ''));
        if ($role === '' && $name === '') {
            continue;
        }
        $nameHtml = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $actor = $url !== ''
            ? '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="flex hover:underline"><span>' . $nameHtml . '</span></a>'
            : '<span>' . $nameHtml . '</span>';
        $html .= "\n\t\t\t" . '<div class="grid grid-cols-2 gap-5 text-p2">'
            . '<div>' . htmlspecialchars($role, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</div>'
            . '<div class="flex flex-wrap">' . $actor . '</div>'
            . '</div>';
    }
    return $html;
}

function uuopera_afisha_admin_cast_label(string $sqlDt): string
{
    static $months = [
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля', 5 => 'мая', 6 => 'июня',
        7 => 'июля', 8 => 'августа', 9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    ];
    if ($sqlDt === '') {
        return 'Состав (без привязки к дате)';
    }
    $ts = strtotime($sqlDt);
    if (!$ts) {
        return $sqlDt;
    }
    return (int) date('j', $ts) . ' ' . ($months[(int) date('n', $ts)] ?? '') . ' ' . date('H:i', $ts);
}

/**
 * @return array<string, list<array{role: string, name: string, url: string}>>
 */
function uuopera_afisha_admin_cast_load_map_raw(int $iblockId, int $elementId): array
{
    $castByDate = [];
    $jsonRaw = uuopera_afisha_admin_read_prop($iblockId, $elementId, 'PARTICIPANTS_JSON');
    if ($jsonRaw !== '') {
        $decoded = json_decode($jsonRaw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $sqlDt => $castHtml) {
                $castByDate[(string) $sqlDt] = uuopera_afisha_admin_cast_parse_html((string) $castHtml);
            }
        }
    }
    if ($castByDate === []) {
        $htmlRaw = uuopera_afisha_admin_read_prop($iblockId, $elementId, 'PARTICIPANTS_HTML');
        if ($htmlRaw !== '') {
            $castByDate[''] = uuopera_afisha_admin_cast_parse_html($htmlRaw);
        }
    }
    return $castByDate;
}

/**
 * @param list<array{label: string, event_id: int, sql_dt: string}> $sessions
 * @return array<string, list<array{role: string, name: string, url: string}>>
 */
function uuopera_afisha_admin_cast_load_map(int $iblockId, int $elementId, array $sessions): array
{
    $castByDate = uuopera_afisha_admin_cast_load_map_raw($iblockId, $elementId);
    foreach ($sessions as $session) {
        $sqlDt = trim((string) ($session['sql_dt'] ?? ''));
        if ($sqlDt !== '' && !array_key_exists($sqlDt, $castByDate)) {
            $castByDate[$sqlDt] = [];
        }
    }
    if ($castByDate === []) {
        $castByDate[''] = [];
    }
    return $castByDate;
}

/**
 * @param array<string, list<array{role: string, name: string, url: string}>> $castByDate
 */
/**
 * @return list<array{label: string, event_id: int, sql_dt: string, cast: list<array{role: string, name: string, url: string}>}>
 */
function uuopera_afisha_admin_load_session_rows(int $iblockId, int $elementId): array
{
    $sessions = uuopera_afisha_admin_parse_sessions(
        uuopera_afisha_admin_read_prop($iblockId, $elementId, 'SESSIONS_JSON')
    );
    $castByDate = uuopera_afisha_admin_cast_load_map_raw($iblockId, $elementId);
    $dateKeys = array_values(array_keys($castByDate));

    if ($sessions === []) {
        if ($castByDate !== []) {
            foreach ($castByDate as $sqlDt => $cast) {
                $sessions[] = [
                    'label' => uuopera_afisha_admin_cast_label((string) $sqlDt),
                    'event_id' => 0,
                    'sql_dt' => (string) $sqlDt,
                    'cast' => $cast,
                ];
            }
        } else {
            $sessions[] = ['label' => '', 'event_id' => 0, 'sql_dt' => '', 'cast' => []];
        }
        return $sessions;
    }

    $rows = [];
    foreach ($sessions as $i => $session) {
        $sqlDt = trim((string) ($session['sql_dt'] ?? ''));
        if ($sqlDt === '' && isset($dateKeys[$i])) {
            $sqlDt = $dateKeys[$i];
        }
        $cast = $castByDate[$sqlDt] ?? [];
        if ($cast === [] && count($sessions) === 1 && isset($castByDate[''])) {
            $sqlDt = '';
            $cast = $castByDate[''];
        }
        $rows[] = [
            'label' => (string) ($session['label'] ?? ''),
            'event_id' => (int) ($session['event_id'] ?? 0),
            'sql_dt' => $sqlDt,
            'cast' => $cast,
        ];
    }

    $usedKeys = array_map(static fn(array $r): string => (string) $r['sql_dt'], $rows);
    foreach ($castByDate as $sqlDt => $cast) {
        if (!in_array((string) $sqlDt, $usedKeys, true)) {
            $rows[] = [
                'label' => uuopera_afisha_admin_cast_label((string) $sqlDt),
                'event_id' => 0,
                'sql_dt' => (string) $sqlDt,
                'cast' => $cast,
            ];
        }
    }

    return $rows;
}

/**
 * @param list<array{label: string, event_id: int, sql_dt: string, cast: list<array{role: string, name: string, url: string}>}> $rows
 */
function uuopera_afisha_admin_save_session_rows(int $elementId, int $iblockId, array $rows): void
{
    $sessionsForJson = [];
    $castByDate = [];
    $nonEmptyDtCount = 0;

    foreach ($rows as $row) {
        $label = trim((string) ($row['label'] ?? ''));
        $eventId = (int) ($row['event_id'] ?? 0);
        $sqlDt = trim((string) ($row['sql_dt'] ?? ''));
        $cast = is_array($row['cast'] ?? null) ? $row['cast'] : [];

        if ($label === '' && $eventId <= 0 && $sqlDt === '' && $cast === []) {
            continue;
        }

        if ($sqlDt !== '') {
            $nonEmptyDtCount++;
        }

        $sessionsForJson[] = [
            'label' => $label,
            'event_id' => $eventId,
            'sql_dt' => $sqlDt,
        ];

        $cleanCast = [];
        foreach ($cast as $actor) {
            if (!is_array($actor)) {
                continue;
            }
            $r = trim((string) ($actor['role'] ?? ''));
            $n = trim((string) ($actor['name'] ?? ''));
            $u = trim((string) ($actor['url'] ?? ''));
            if ($r !== '' || $n !== '') {
                $cleanCast[] = ['role' => $r, 'name' => $n, 'url' => $u];
            }
        }

        $useSingleKey = count($rows) === 1 && $nonEmptyDtCount === 0;
        $key = $useSingleKey ? '' : $sqlDt;

        if ($key !== '' || $cleanCast !== []) {
            if ($key === '' && !$useSingleKey) {
                continue;
            }
            $castByDate[$key] = $cleanCast;
        }
    }

    CIBlockElement::SetPropertyValuesEx(
        $elementId,
        $iblockId,
        ['SESSIONS_JSON' => uuopera_afisha_admin_build_sessions_json($sessionsForJson)]
    );
    uuopera_afisha_admin_cast_save($elementId, $iblockId, $castByDate);
}

function uuopera_afisha_admin_cast_save(int $elementId, int $iblockId, array $castByDate): void
{
    $newMap = [];
    foreach ($castByDate as $sqlDt => $rows) {
        $html = uuopera_afisha_admin_cast_build_html($rows);
        if ($html !== '' || $sqlDt !== '') {
            $newMap[$sqlDt] = $html;
        }
    }
    $jsonStr = $newMap !== [] ? json_encode($newMap, JSON_UNESCAPED_UNICODE) : '';
    CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, ['PARTICIPANTS_JSON' => $jsonStr]);

    if (count($newMap) === 1) {
        $firstHtml = reset($newMap);
        $phpHtml = '<div class="flex flex-col gap-6 md:col-span-6 xl:col-span-4">'
            . "\n\t" . '<div class="grid grid-cols-2 gap-5">'
            . "\n\t\t" . '<div class="text-h2">Состав</div>'
            . "\n\t" . '</div>'
            . "\n\t" . '<div class="flex flex-col gap-3" data-particiants-container>'
            . $firstHtml
            . "\n\t" . '</div>'
            . "\n" . '</div>';
        CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, ['PARTICIPANTS_HTML' => $phpHtml]);
    }
}

function uuopera_afisha_admin_embedded_begin(string $title): void
{
    global $APPLICATION;
    $APPLICATION->SetTitle($title);
    ?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<link rel="stylesheet" type="text/css" href="/bitrix/themes/.default/adminstyles.css">
<style>
body { margin: 0; padding: 12px 16px 20px; background: #fff; font-family: var(--ui-font-family-primary, Arial, sans-serif); }
.uuopera-admin-hint { font-size: 12px; color: #666; margin: 0 0 12px; line-height: 1.4; }
.uuopera-admin-msg { margin-bottom: 12px; padding: 8px 12px; background: #e8f5e9; border: 1px solid #a5d6a7; font-size: 13px; }
.cast-table input[type="text"] { width: 100%; box-sizing: border-box; padding: 4px 6px; }
.uuopera-session-block { margin-bottom: 24px; padding: 12px 14px; border: 1px solid #ddd; background: #fafafa; }
.uuopera-session-block h4 { margin: 0 0 10px; font-size: 14px; }
.uuopera-session-fields { display: grid; grid-template-columns: 1fr 140px 1fr; gap: 8px; margin-bottom: 12px; }
.uuopera-session-fields label { display: block; font-size: 11px; color: #666; margin-bottom: 2px; }
.uuopera-session-fields input { width: 100%; box-sizing: border-box; padding: 4px 6px; }
</style>
</head>
<body class="adm-workarea">
    <?php
}

function uuopera_afisha_admin_embedded_end(): void
{
    ?>
</body>
</html>
    <?php
    die();
}

/**
 * @return list<int>
 */
function uuopera_afisha_admin_hidden_property_ids(int $iblockId): array
{
    $ids = [];
    foreach (['SESSIONS_JSON', 'PARTICIPANTS_HTML', 'PARTICIPANTS_JSON'] as $code) {
        $res = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code]);
        if ($row = $res->Fetch()) {
            $ids[] = (int) $row['ID'];
        }
    }
    return $ids;
}

function uuopera_afisha_admin_tab_engine_handler(): array
{
    return [
        'TABSET' => 'uuopera_afisha',
        'GetTabs' => 'uuopera_afisha_admin_get_tabs',
        'ShowTab' => 'uuopera_afisha_admin_show_tab',
    ];
}

/**
 * @param array<string, mixed> $iblockElementInfo
 * @return list<array<string, mixed>>|null
 */
function uuopera_afisha_admin_get_tabs(array $iblockElementInfo): ?array
{
    $iblockId = uuopera_afisha_events_iblock_id();
    $elementIblockId = (int) ($iblockElementInfo['IBLOCK']['ID'] ?? 0);
    if ($iblockId <= 0 || $elementIblockId !== $iblockId) {
        return null;
    }
    $elementId = (int) ($iblockElementInfo['ID'] ?? 0);
    if ($elementId <= 0) {
        return null;
    }
    if (class_exists(\Bitrix\Main\Context::class)) {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        if ($request->get('action') === 'copy') {
            return null;
        }
    } elseif (($_REQUEST['action'] ?? '') === 'copy') {
        return null;
    }

    return [
        [
            'DIV' => 'sessions',
            'SORT' => 350,
            'TAB' => 'Сеансы и состав',
            'TITLE' => 'Даты показов, билеты Radario и артисты по датам',
        ],
    ];
}

/**
 * @param array<string, mixed> $iblockElementInfo
 */
function uuopera_afisha_admin_show_tab(string $div, array $iblockElementInfo): void
{
    $elementId = (int) ($iblockElementInfo['ID'] ?? 0);
    if ($elementId <= 0) {
        return;
    }
    if ($div !== 'sessions') {
        return;
    }
    $url = '/local/admin/afisha_sessions_edit.php?id=' . $elementId . '&embedded=1&sessid=' . bitrix_sessid();
    ?>
<tr>
    <td colspan="2">
        <iframe src="<?= htmlspecialchars($url) ?>"
            style="width:100%;min-height:900px;border:none;display:block"
            id="uuopera-afisha-sessions-iframe"></iframe>
    </td>
</tr>
    <?php
}

function uuopera_afisha_admin_hide_technical_props_epilog(): void
{
    if (!defined('ADMIN_SECTION') || ADMIN_SECTION !== true) {
        return;
    }
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    if (!str_contains($script, 'iblock_element_edit.php')) {
        return;
    }
    $iblockId = uuopera_afisha_events_iblock_id();
    if ($iblockId <= 0 || (int) ($_REQUEST['IBLOCK_ID'] ?? 0) !== $iblockId) {
        return;
    }
    $propIds = uuopera_afisha_admin_hidden_property_ids($iblockId);
    if ($propIds === []) {
        return;
    }
    $css = [];
    foreach ($propIds as $pid) {
        $css[] = '#tr_PROPERTY_' . $pid . ' { display: none !important; }';
    }
    echo '<style id="uuopera-afisha-hide-props">' . implode("\n", $css) . '</style>';
    echo '<script>document.addEventListener("DOMContentLoaded",function(){'
        . 'var n=document.querySelector(".adm-detail-tab[onclick*=\\"edit2\\"]");'
        . 'if(n&&!n.textContent.match(/Сеанс/)){n.style.opacity="0.85";}'
        . '});</script>';
}
