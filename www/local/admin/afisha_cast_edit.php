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
$msg = $saved ? 'Состав сохранён.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $elementId > 0 && check_bitrix_sessid()) {
    $castPost = (array) ($_POST['cast'] ?? []);
    $castByDate = [];
    foreach ($castPost as $sqlDt => $rows) {
        if (!is_array($rows)) {
            continue;
        }
        $clean = [];
        foreach ($rows as $row) {
            $r = trim((string) ($row['role'] ?? ''));
            $n = trim((string) ($row['name'] ?? ''));
            $u = trim((string) ($row['url'] ?? ''));
            if ($r !== '' || $n !== '') {
                $clean[] = ['role' => $r, 'name' => $n, 'url' => $u];
            }
        }
        $castByDate[(string) $sqlDt] = $clean;
    }
    $newDates = (array) ($_POST['new_dates'] ?? []);
    foreach ($newDates as $sqlDt) {
        $sqlDt = trim((string) $sqlDt);
        if ($sqlDt !== '' && !array_key_exists($sqlDt, $castByDate)) {
            $castByDate[$sqlDt] = [];
        }
    }
    uuopera_afisha_admin_cast_save($elementId, $iblockId, $castByDate);

    $redirect = '/local/admin/afisha_sessions_edit.php?id=' . $elementId . '&saved=1';
    if ($embedded) {
        $redirect .= '&embedded=1';
    }
    LocalRedirect($redirect);
}

$element = null;
$castByDate = [];
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
        $sessions = uuopera_afisha_admin_parse_sessions(
            uuopera_afisha_admin_read_prop($iblockId, $elementId, 'SESSIONS_JSON')
        );
        $castByDate = uuopera_afisha_admin_cast_load_map($iblockId, $elementId, $sessions);
    }
}

$allElements = [];
if (!$elementId) {
    $res = CIBlockElement::GetList(
        ['NAME' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N'],
        false,
        false,
        ['ID', 'NAME', 'CODE']
    );
    while ($row = $res->GetNext()) {
        $allElements[] = $row;
    }
}

$title = $element ? 'Состав: ' . (string) $element['~NAME'] : 'Редактирование состава афиши';

if ($embedded) {
    uuopera_afisha_admin_embedded_begin($title);
} else {
    $APPLICATION->SetTitle($title);
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
}

if ($iblockId <= 0): ?>
<p style="color:red">Инфоблок событий афиши не найден. Запустите установочный скрипт.</p>
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

if (!$elementId || !$element):
    if (!$embedded): ?>
<p><a href="/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=<?= $iblockId ?>&type=uuopera&lang=ru">← Список событий</a></p>
<table class="adm-list-table" style="width:100%">
<thead><tr class="adm-list-table-header">
    <td>ID</td><td>Название</td><td>Код</td><td></td>
</tr></thead>
<tbody>
<?php foreach ($allElements as $el): ?>
<tr class="adm-list-table-row">
    <td><?= (int) $el['ID'] ?></td>
    <td><?= htmlspecialchars((string) $el['~NAME']) ?></td>
    <td><?= htmlspecialchars((string) $el['~CODE']) ?></td>
    <td><a href="?id=<?= (int) $el['ID'] ?>">Редактировать состав</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php
    else: ?>
<p>Откройте редактор из карточки события афиши (вкладка «Состав»).</p>
<?php
    endif;
    if ($embedded) {
        uuopera_afisha_admin_embedded_end();
    }
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    die();
endif;

if (!$embedded): ?>
<p style="margin-bottom:12px">
    <a href="/local/admin/afisha_cast_edit.php">← Все события</a>
    &nbsp;|&nbsp;
    <a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=<?= $iblockId ?>&type=uuopera&ID=<?= $elementId ?>&lang=ru" target="_blank">
        Карточка в Bitrix ↗
    </a>
    &nbsp;|&nbsp;
    <a href="/afisha/<?= htmlspecialchars((string) ($element['~CODE'] ?? '')) ?>/" target="_blank">На сайте ↗</a>
</p>
<?php else: ?>
<p class="uuopera-admin-hint">
    Роли и артисты по датам. Ключи дат задаются на вкладке «Сеансы» (поле «Ключ даты») или добавьте дату ниже.
</p>
<?php endif; ?>

<form method="post" action="/local/admin/afisha_cast_edit.php?id=<?= $elementId ?><?= $embedded ? '&embedded=1' : '' ?>">
<?= bitrix_sessid_post() ?>

<?php foreach ($castByDate as $sqlDt => $entries): ?>
<div style="margin-bottom:28px">
    <h3 style="margin:0 0 10px;padding:8px 12px;background:#f0f0f0;border-left:4px solid #5b7fbe">
        <?= htmlspecialchars(uuopera_afisha_admin_cast_label($sqlDt)) ?>
        <?php if ($sqlDt !== ''): ?>
        <span style="font-size:11px;font-weight:normal;color:#666;margin-left:8px"><?= htmlspecialchars($sqlDt) ?></span>
        <?php endif; ?>
    </h3>
    <table class="adm-list-table cast-table" style="width:100%">
        <thead>
            <tr class="adm-list-table-header">
                <td style="width:25%">Роль</td>
                <td style="width:35%">Имя артиста</td>
                <td style="width:30%">Ссылка (/persone/…)</td>
                <td style="width:10%"></td>
            </tr>
        </thead>
        <tbody class="cast-rows">
        <?php foreach ($entries as $i => $entry): ?>
            <tr class="adm-list-table-row">
                <td><input type="text" name="cast[<?= htmlspecialchars($sqlDt) ?>][<?= $i ?>][role]"
                    value="<?= htmlspecialchars($entry['role']) ?>" placeholder="дирижер"></td>
                <td><input type="text" name="cast[<?= htmlspecialchars($sqlDt) ?>][<?= $i ?>][name]"
                    value="<?= htmlspecialchars($entry['name']) ?>" placeholder="Иванов И. И."></td>
                <td><input type="text" name="cast[<?= htmlspecialchars($sqlDt) ?>][<?= $i ?>][url]"
                    value="<?= htmlspecialchars($entry['url']) ?>" placeholder="/persone/ivanov/"></td>
                <td style="text-align:center">
                    <button type="button" class="adm-btn" onclick="castRemoveRow(this)">✕</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" class="adm-btn" onclick="castAddRow(this, <?= json_encode($sqlDt, JSON_UNESCAPED_UNICODE) ?>)"
        style="margin-top:6px">+ Добавить артиста</button>
</div>
<?php endforeach; ?>

<div style="margin:16px 0;padding:12px;background:#fafafa;border:1px dashed #ccc">
    <strong>Новая дата состава</strong>
    <p class="uuopera-admin-hint" style="margin:4px 0 8px">Формат: <code>2026-05-27 19:00:00</code> — должен совпадать с ключом на вкладке «Сеансы».</p>
    <input type="text" name="new_dates[]" placeholder="2026-06-01 19:00:00" style="width:280px;padding:4px 6px">
</div>

<div style="margin-top:16px;padding-top:12px;border-top:1px solid #ddd">
    <input type="submit" class="adm-btn adm-btn-save" value="Сохранить состав">
</div>
</form>

<script>
var castRowIndex = {};
function castAddRow(btn, sqlDt) {
    if (!castRowIndex[sqlDt]) castRowIndex[sqlDt] = 1000;
    var idx = castRowIndex[sqlDt]++;
    var table = btn.previousElementSibling.querySelector('.cast-rows');
    var tr = document.createElement('tr');
    tr.className = 'adm-list-table-row';
    tr.innerHTML =
        '<td><input type="text" name="cast['+sqlDt+']['+idx+'][role]" placeholder="роль"></td>' +
        '<td><input type="text" name="cast['+sqlDt+']['+idx+'][name]" placeholder="Имя Фамилия"></td>' +
        '<td><input type="text" name="cast['+sqlDt+']['+idx+'][url]" placeholder="/persone/slug/"></td>' +
        '<td style="text-align:center"><button type="button" class="adm-btn" onclick="castRemoveRow(this)">✕</button></td>';
    table.appendChild(tr);
    tr.querySelector('input').focus();
}
function castRemoveRow(btn) {
    if (!confirm('Удалить строку?')) return;
    btn.closest('tr').remove();
}
</script>
<?php
if ($embedded) {
    uuopera_afisha_admin_embedded_end();
}
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
