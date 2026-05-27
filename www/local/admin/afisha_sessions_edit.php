<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещён');
    die();
}
if (!Loader::includeModule('iblock')) {
    echo 'Модуль iblock не установлен';
    die();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events_bootstrap.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_events.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/uuopera_afisha_admin.php';

$embedded = uuopera_afisha_admin_embedded();
$iblockId = uuopera_afisha_events_iblock_id();
$elementId = (int) ($_REQUEST['id'] ?? 0);
$saved = (bool) ($_GET['saved'] ?? false);
$msg = $saved ? 'Сеансы и состав сохранены.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $elementId > 0 && check_bitrix_sessid()) {
    $rowsPost = (array) ($_POST['sessions'] ?? []);
    $rows = [];
    foreach ($rowsPost as $row) {
        if (!is_array($row)) {
            continue;
        }
        $cast = [];
        foreach ((array) ($row['cast'] ?? []) as $actor) {
            if (!is_array($actor)) {
                continue;
            }
            $cast[] = [
                'role' => trim((string) ($actor['role'] ?? '')),
                'name' => trim((string) ($actor['name'] ?? '')),
                'url' => trim((string) ($actor['url'] ?? '')),
            ];
        }
        $rows[] = [
            'label' => trim((string) ($row['label'] ?? '')),
            'event_id' => (int) ($row['event_id'] ?? 0),
            'sql_dt' => trim((string) ($row['sql_dt'] ?? '')),
            'cast' => $cast,
        ];
    }
    uuopera_afisha_admin_save_session_rows($elementId, $iblockId, $rows);

    $redirect = '/local/admin/afisha_sessions_edit.php?id=' . $elementId . '&saved=1';
    if ($embedded) {
        $redirect .= '&embedded=1';
    }
    LocalRedirect($redirect);
}

$element = null;
$sessions = [];

if ($elementId > 0) {
    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'CODE']
    );
    $element = $res->GetNext();
    if ($element) {
        $sessions = uuopera_afisha_admin_load_session_rows($iblockId, $elementId);
    }
}

$title = $element ? 'Сеансы и состав: ' . (string) $element['~NAME'] : 'Сеансы афиши';

if ($embedded) {
    uuopera_afisha_admin_embedded_begin($title);
} else {
    $APPLICATION->SetTitle($title);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

if ($iblockId <= 0): ?>
<p style="color:red">Инфоблок событий афиши не найден.</p>
<?php
    if ($embedded) {
        uuopera_afisha_admin_embedded_end();
    }
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    die();
endif;

if ($msg !== ''): ?>
<div class="uuopera-admin-msg"><?= htmlspecialchars($msg) ?></div>
<?php endif;

if (!$elementId || !$element): ?>
<p>Укажите ID события: <code>?id=…</code></p>
<?php
    if ($embedded) {
        uuopera_afisha_admin_embedded_end();
    }
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    die();
endif;

$multiDate = count($sessions) > 1
    || count(array_filter($sessions, static fn(array $s): bool => trim((string) ($s['sql_dt'] ?? '')) !== '')) > 1;
?>

<p class="uuopera-admin-hint">
    У каждого сеанса — дата на сайте, Radario eventId и состав артистов на эту дату.
    <?php if ($multiDate): ?>
    При нескольких датах укажите ключ <code>2026-05-27 19:00:00</code> — по нему на сайте переключается состав.
    <?php endif; ?>
</p>

<form method="post" action="/local/admin/afisha_sessions_edit.php?id=<?= $elementId ?><?= $embedded ? '&embedded=1' : '' ?>">
<?= bitrix_sessid_post() ?>

<div id="uuopera-sessions-list">
<?php foreach ($sessions as $si => $session): ?>
<?php
    $cast = is_array($session['cast'] ?? null) ? $session['cast'] : [];
    if ($cast === []) {
        $cast[] = ['role' => '', 'name' => '', 'url' => ''];
    }
    $sqlDt = (string) ($session['sql_dt'] ?? '');
?>
<div class="uuopera-session-block" data-session-index="<?= (int) $si ?>">
    <h4>Сеанс <?= (int) $si + 1 ?><?= $session['label'] !== '' ? ': ' . htmlspecialchars((string) $session['label']) : '' ?></h4>
    <div class="uuopera-session-fields">
        <div>
            <label>Дата на сайте</label>
            <input type="text" name="sessions[<?= $si ?>][label]" value="<?= htmlspecialchars((string) $session['label']) ?>" placeholder="27 мая 19:00">
        </div>
        <div>
            <label>Radario eventId</label>
            <input type="number" name="sessions[<?= $si ?>][event_id]" min="0"
                value="<?= (int) $session['event_id'] > 0 ? (int) $session['event_id'] : '' ?>" placeholder="123456">
        </div>
        <div>
            <label>Ключ даты<?= $multiDate ? ' *' : '' ?></label>
            <input type="text" name="sessions[<?= $si ?>][sql_dt]" value="<?= htmlspecialchars($sqlDt) ?>"
                placeholder="2026-05-27 19:00:00"<?= $multiDate ? ' required' : '' ?>>
        </div>
    </div>
    <table class="adm-list-table cast-table" style="width:100%;margin-bottom:6px">
        <thead>
            <tr class="adm-list-table-header">
                <td style="width:25%">Роль</td>
                <td style="width:35%">Артист</td>
                <td style="width:30%">Ссылка (/persone/…)</td>
                <td style="width:10%"></td>
            </tr>
        </thead>
        <tbody class="uuopera-cast-rows">
        <?php foreach ($cast as $ci => $actor): ?>
            <tr class="adm-list-table-row">
                <td><input type="text" name="sessions[<?= $si ?>][cast][<?= $ci ?>][role]"
                    value="<?= htmlspecialchars((string) $actor['role']) ?>" placeholder="дирижер"></td>
                <td><input type="text" name="sessions[<?= $si ?>][cast][<?= $ci ?>][name]"
                    value="<?= htmlspecialchars((string) $actor['name']) ?>" placeholder="Иванов И. И."></td>
                <td><input type="text" name="sessions[<?= $si ?>][cast][<?= $ci ?>][url]"
                    value="<?= htmlspecialchars((string) $actor['url']) ?>" placeholder="/persone/ivanov/"></td>
                <td style="text-align:center">
                    <button type="button" class="adm-btn" onclick="uuoperaCastRemove(this)">✕</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" class="adm-btn" onclick="uuoperaCastAdd(this)">+ Артист</button>
    <button type="button" class="adm-btn" onclick="uuoperaSessionRemove(this)" style="float:right">Удалить сеанс</button>
    <div style="clear:both"></div>
</div>
<?php endforeach; ?>
</div>

<p style="margin-top:12px">
    <button type="button" class="adm-btn" onclick="uuoperaSessionAdd()">+ Добавить сеанс</button>
</p>
<p style="margin-top:16px;padding-top:12px;border-top:1px solid #ddd">
    <input type="submit" class="adm-btn adm-btn-save" value="Сохранить сеансы и состав">
</p>
</form>

<template id="uuopera-session-tpl">
<div class="uuopera-session-block" data-session-index="__IDX__">
    <h4>Новый сеанс</h4>
    <div class="uuopera-session-fields">
        <div>
            <label>Дата на сайте</label>
            <input type="text" name="sessions[__IDX__][label]" placeholder="27 мая 19:00">
        </div>
        <div>
            <label>Radario eventId</label>
            <input type="number" name="sessions[__IDX__][event_id]" min="0" placeholder="123456">
        </div>
        <div>
            <label>Ключ даты</label>
            <input type="text" name="sessions[__IDX__][sql_dt]" placeholder="2026-05-27 19:00:00">
        </div>
    </div>
    <table class="adm-list-table cast-table" style="width:100%;margin-bottom:6px">
        <thead>
            <tr class="adm-list-table-header">
                <td style="width:25%">Роль</td>
                <td style="width:35%">Артист</td>
                <td style="width:30%">Ссылка</td>
                <td style="width:10%"></td>
            </tr>
        </thead>
        <tbody class="uuopera-cast-rows"></tbody>
    </table>
    <button type="button" class="adm-btn" onclick="uuoperaCastAdd(this)">+ Артист</button>
    <button type="button" class="adm-btn" onclick="uuoperaSessionRemove(this)" style="float:right">Удалить сеанс</button>
    <div style="clear:both"></div>
</div>
</template>

<script>
var uuoperaSessionIdx = <?= count($sessions) + 100 ?>;
var uuoperaCastIdx = 1000;

function uuoperaCastAdd(btn) {
    var block = btn.closest('.uuopera-session-block');
    var si = block.getAttribute('data-session-index');
    var tbody = block.querySelector('.uuopera-cast-rows');
    var ci = uuoperaCastIdx++;
    var tr = document.createElement('tr');
    tr.className = 'adm-list-table-row';
    tr.innerHTML =
        '<td><input type="text" name="sessions['+si+'][cast]['+ci+'][role]" placeholder="роль"></td>' +
        '<td><input type="text" name="sessions['+si+'][cast]['+ci+'][name]" placeholder="Имя Фамилия"></td>' +
        '<td><input type="text" name="sessions['+si+'][cast]['+ci+'][url]" placeholder="/persone/slug/"></td>' +
        '<td style="text-align:center"><button type="button" class="adm-btn" onclick="uuoperaCastRemove(this)">✕</button></td>';
    tbody.appendChild(tr);
    tr.querySelector('input').focus();
}

function uuoperaCastRemove(btn) {
    var tbody = btn.closest('tbody');
    if (tbody.querySelectorAll('tr').length <= 1) {
        btn.closest('tr').querySelectorAll('input').forEach(function(inp) { inp.value = ''; });
        return;
    }
    btn.closest('tr').remove();
}

function uuoperaSessionAdd() {
    var tpl = document.getElementById('uuopera-session-tpl').innerHTML.replace(/__IDX__/g, String(uuoperaSessionIdx++));
    var wrap = document.createElement('div');
    wrap.innerHTML = tpl;
    var block = wrap.firstElementChild;
    document.getElementById('uuopera-sessions-list').appendChild(block);
    uuoperaCastAdd(block.querySelector('.adm-btn'));
}

function uuoperaSessionRemove(btn) {
    if (!confirm('Удалить сеанс и его состав?')) return;
    var list = document.getElementById('uuopera-sessions-list');
    if (list.querySelectorAll('.uuopera-session-block').length <= 1) {
        alert('Должен остаться хотя бы один сеанс.');
        return;
    }
    btn.closest('.uuopera-session-block').remove();
}
</script>
<?php
if ($embedded) {
    uuopera_afisha_admin_embedded_end();
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
