<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
if (!in_array($_SESSION['role'] ?? '', ['admin','chief'], true)) {
    http_response_code(403);
    echo '<h1>403</h1><p>Доступ только Гл. Куратору и Администратору.</p>'; exit;
}
require_once 'db.php';
require_once 'user_header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>FUTURAMA STAFF | Почты стаффа</title>
<link rel="stylesheet" href="index.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .em-wrap { max-width: 1100px; margin: 0 auto; }
    .em-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
    .em-head h1 { margin: 0; font-size: 1.5rem; font-weight: 800; }
    .em-btn { padding: 0.55rem 1rem; border-radius: 8px; cursor: pointer; font-weight: 700; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: #fff; font-size: 0.88rem; }
    .em-btn.primary { background: var(--accent,#7c3aed); border-color: transparent; }
    .em-btn.danger { background: rgba(239,68,68,0.15); border-color: rgba(239,68,68,0.3); color: #fca5a5; }
    .em-btn:hover { filter: brightness(1.1); }

    .em-search { padding: 0.65rem 1rem; border-radius: 10px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); color: #fff; font-size: 0.9rem; flex: 1; min-width: 220px; }

    .em-table { width: 100%; border-collapse: collapse; background: rgba(20,20,30,0.55); border-radius: 12px; overflow: hidden; }
    .em-table th, .em-table td { padding: 0.7rem 0.9rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 0.9rem; }
    .em-table th { background: rgba(0,0,0,0.3); color: #aaa; font-size: 0.72rem; text-transform: uppercase; }
    .em-table tr:hover { background: rgba(255,255,255,0.02); }
    .em-table tr.orphan { background: rgba(239,68,68,0.08); }
    .em-table tr.orphan td:first-child::after { content: ' (нет в таблице)'; color: #fca5a5; font-size: 0.75rem; font-weight: 500; }

    .em-input {
        width: 100%; padding: 0.4rem 0.6rem; background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; color: #fff; font-size: 0.85rem; font-family: inherit;
    }
    .em-input:focus { outline: none; border-color: var(--accent,#7c3aed); background: rgba(255,255,255,0.08); }

    .em-pill { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 10px; font-size: 0.7rem; font-weight: 700; background: rgba(124,58,237,0.2); color: #c4b5fd; }

    .em-stats { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; }
    .em-stat { padding: 0.7rem 1rem; background: rgba(124,58,237,0.1); border: 1px solid rgba(124,58,237,0.2); border-radius: 10px; }
    .em-stat .num { font-size: 1.3rem; font-weight: 800; color: #fff; display: block; }
    .em-stat .lbl { font-size: 0.72rem; color: #aaa; text-transform: uppercase; }
</style>
</head>
<body>
<?php include 'sidebar_v2.php'; ?>
<main class="main-content">
    <div class="em-wrap">
        <div class="em-head">
            <h1><i class="fa-solid fa-envelopes-bulk"></i> Почты стаффа</h1>
        </div>

        <div class="em-stats">
            <div class="em-stat"><span class="num" id="cntTotal">—</span><span class="lbl">Всего в составе</span></div>
            <div class="em-stat"><span class="num" id="cntFilled">—</span><span class="lbl">С почтой</span></div>
            <div class="em-stat"><span class="num" id="cntOrphan">—</span><span class="lbl">Не в таблице</span></div>
        </div>

        <div class="em-head">
            <input id="searchInput" class="em-search" placeholder="Поиск по нику / почте...">
            <button class="em-btn primary" onclick="syncEmails()" title="Удалит почты тех, кого больше нет в Google-таблице"><i class="fa-solid fa-rotate"></i> Сверить с таблицей</button>
            <button class="em-btn" onclick="loadList()"><i class="fa-solid fa-arrows-rotate"></i> Обновить</button>
        </div>

        <div style="overflow-x:auto;">
            <table class="em-table">
                <thead>
                    <tr>
                        <th>Никнейм</th>
                        <th>Discord ID</th>
                        <th>Роль</th>
                        <th>Почта</th>
                        <th>Заметка</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="emailsBody"><tr><td colspan="6" style="text-align:center; color:#888; padding:1.5rem;">Загрузка...</td></tr></tbody>
            </table>
        </div>
    </div>
</main>

<script>
let allRows = [];

function render() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    const rows = allRows.filter(r => !q || (r.nickname||'').toLowerCase().includes(q) || (r.email||'').toLowerCase().includes(q));
    const $tbody = document.getElementById('emailsBody');
    if (!rows.length) { $tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#888;">Ничего не найдено</td></tr>'; return; }

    $tbody.innerHTML = rows.map((r, i) => `
        <tr class="${r.in_sheet ? '' : 'orphan'}" data-nick="${(r.nickname||'').replace(/"/g,'&quot;')}">
            <td><b>${r.nickname}</b></td>
            <td><code style="color:#888; font-size:0.78rem">${r.discord_id || '—'}</code></td>
            <td>${r.role ? '<span class="em-pill">' + r.role + '</span>' : '—'}</td>
            <td><input class="em-input" type="email" placeholder="например: ivan@gmail.com" value="${(r.email||'').replace(/"/g,'&quot;')}" data-field="email"></td>
            <td><input class="em-input" placeholder="..." value="${(r.note||'').replace(/"/g,'&quot;')}" data-field="note"></td>
            <td><button class="em-btn primary" style="padding:0.35rem 0.7rem; font-size:0.78rem;" onclick="saveRow(this)"><i class="fa-solid fa-floppy-disk"></i></button></td>
        </tr>
    `).join('');

    // Стата
    document.getElementById('cntTotal').textContent = allRows.filter(r => r.in_sheet).length;
    document.getElementById('cntFilled').textContent = allRows.filter(r => r.email).length;
    document.getElementById('cntOrphan').textContent = allRows.filter(r => !r.in_sheet).length;
}

async function loadList() {
    try {
        const r = await fetch('api.php?action=emails_list');
        const j = await r.json();
        if (!j.success) { document.getElementById('emailsBody').innerHTML = `<tr><td colspan="6" style="color:#fca5a5">Ошибка: ${j.error||'?'}</td></tr>`; return; }
        allRows = j.rows;
        render();
    } catch (e) {
        document.getElementById('emailsBody').innerHTML = `<tr><td colspan="6" style="color:#fca5a5">Сеть: ${e.message}</td></tr>`;
    }
}

async function saveRow(btn) {
    const tr = btn.closest('tr');
    const nick = tr.dataset.nick;
    const email = tr.querySelector('[data-field="email"]').value.trim();
    const note = tr.querySelector('[data-field="note"]').value.trim();
    const did = (allRows.find(r => r.nickname === nick) || {}).discord_id || '';

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    try {
        const r = await fetch('api.php?action=emails_set', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nickname: nick, email, note, discord_id: did })
        });
        const j = await r.json();
        if (!j.success) { alert('Ошибка: ' + (j.error||'?')); }
        else {
            btn.innerHTML = '<i class="fa-solid fa-check"></i>';
            setTimeout(() => btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i>', 1500);
            const item = allRows.find(r => r.nickname === nick);
            if (item) { item.email = email; item.note = note; render(); }
        }
    } catch (e) { alert('Сеть: ' + e.message); }
    btn.disabled = false;
}

async function syncEmails() {
    if (!confirm('Сверить с Google-таблицей? Будут удалены почты тех, кого больше нет в листе «Смены».')) return;
    const r = await fetch('api.php?action=emails_sync', { method: 'POST' });
    const j = await r.json();
    if (!j.success) { alert('Ошибка: ' + (j.error||'?')); return; }
    alert(`Готово. Удалено почт: ${j.removed_count}` + (j.removed_count ? `\n\nКого: ${j.removed.join(', ')}` : ''));
    loadList();
}

document.getElementById('searchInput').addEventListener('input', render);
loadList();
</script>
</body>
</html>
