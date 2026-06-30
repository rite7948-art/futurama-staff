<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo '<h1>403</h1><p>Доступ только администраторам.</p>'; exit;
}
require_once 'db.php';
require_once 'user_header.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>FUTURAMA STAFF | Посещаемость</title>
<link rel="stylesheet" href="index.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .vs-wrap { max-width: 1200px; margin: 0 auto; }
    .vs-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; margin-bottom: 1.2rem; }
    @media (max-width: 900px) { .vs-grid { grid-template-columns: 1fr; } }
    .vs-card { background: rgba(20,20,30,0.55); border-radius: 12px; padding: 1rem 1.2rem; }
    .vs-card h3 { margin: 0 0 0.8rem; font-size: 0.95rem; font-weight: 700; color: #aaa; text-transform: uppercase; letter-spacing: 0.05em; }
    .vs-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0.7rem; margin-bottom: 1rem; }
    .vs-stat { background: rgba(124,58,237,0.1); border: 1px solid rgba(124,58,237,0.2); border-radius: 10px; padding: 0.7rem; text-align: center; }
    .vs-stat .num { font-size: 1.6rem; font-weight: 800; color: #fff; }
    .vs-stat .lbl { font-size: 0.75rem; color: #aaa; text-transform: uppercase; }
    .vs-online { display: flex; flex-direction: column; gap: 0.4rem; max-height: 280px; overflow-y: auto; }
    .vs-online-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.6rem; background: rgba(16,185,129,0.08); border-left: 3px solid #10b981; border-radius: 6px; font-size: 0.85rem; }
    .vs-online-item .dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse 1.5s infinite; }
    @keyframes pulse { 50% { opacity: 0.4; } }
    .vs-top { display: flex; flex-direction: column; gap: 0.4rem; }
    .vs-top-item { display: flex; justify-content: space-between; padding: 0.4rem 0.6rem; background: rgba(255,255,255,0.03); border-radius: 6px; font-size: 0.85rem; }
    .vs-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .vs-table th, .vs-table td { padding: 0.5rem 0.7rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); }
    .vs-table th { background: rgba(0,0,0,0.3); color: #aaa; font-size: 0.72rem; text-transform: uppercase; }
    .vs-table tr:hover { background: rgba(255,255,255,0.02); }
    .vs-filters { display: flex; gap: 0.5rem; margin-bottom: 0.8rem; flex-wrap: wrap; }
    .vs-input { padding: 0.5rem 0.9rem; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: #fff; font-size: 0.9rem; }
    .vs-pill { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 10px; font-size: 0.7rem; font-weight: 700; }
    .vs-pill.admin { background: #ef4444; color: #fff; }
    .vs-pill.chief { background: #8b5cf6; color: #fff; }
    .vs-pill.curator { background: #10b981; color: #fff; }
    .vs-pill.master { background: #3b82f6; color: #fff; }
</style>
</head>
<body>
<?php include 'sidebar_v2.php'; ?>
<main class="main-content">
    <div class="vs-wrap">
        <h1 style="margin:0 0 1.2rem; font-size:1.5rem;"><i class="fa-solid fa-chart-line"></i> Посещаемость сайта</h1>

        <div class="vs-grid">
            <div class="vs-card">
                <div class="vs-stats">
                    <div class="vs-stat"><div class="num" id="todayVisits">—</div><div class="lbl">Визитов сегодня</div></div>
                    <div class="vs-stat"><div class="num" id="todayUsers">—</div><div class="lbl">Юзеров сегодня</div></div>
                </div>
                <h3><i class="fa-solid fa-trophy"></i> Топ за неделю</h3>
                <div class="vs-top" id="topWeek"></div>
            </div>

            <div class="vs-card">
                <h3><i class="fa-solid fa-circle" style="color:#10b981; font-size:0.7rem;"></i> Онлайн сейчас (5 мин)</h3>
                <div class="vs-online" id="onlineList"></div>
            </div>
        </div>

        <div class="vs-card">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> Последние посещения</h3>
            <div class="vs-filters">
                <input id="filterUser" class="vs-input" placeholder="Поиск по нику...">
                <input id="filterPage" class="vs-input" placeholder="Поиск по странице...">
                <button class="vs-input" onclick="reload()" style="background:var(--accent,#7c3aed); border-color:transparent; cursor:pointer;"><i class="fa-solid fa-rotate"></i> Обновить</button>
            </div>
            <div style="overflow-x:auto;">
                <table class="vs-table">
                    <thead><tr><th>Юзер</th><th>Роль</th><th>Страница</th><th>IP</th><th>Время</th></tr></thead>
                    <tbody id="visitsBody"><tr><td colspan="5" style="text-align:center; color:#888; padding:1rem;">Загрузка...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
const $tbody = document.getElementById('visitsBody');

function fmt(t) {
    if (!t) return '';
    const d = new Date(t.replace(' ', 'T') + 'Z');
    return d.toLocaleString('ru-RU', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit' });
}
function fmtAgo(t) {
    if (!t) return '';
    const ms = Date.now() - new Date(t.replace(' ', 'T') + 'Z').getTime();
    const s = Math.floor(ms/1000);
    if (s < 60) return s + ' сек назад';
    if (s < 3600) return Math.floor(s/60) + ' мин назад';
    return Math.floor(s/3600) + ' ч назад';
}

async function reload() {
    const u = encodeURIComponent(document.getElementById('filterUser').value.trim());
    const p = encodeURIComponent(document.getElementById('filterPage').value.trim());
    const r = await fetch(`api.php?action=visits_list&user=${u}&page=${p}&limit=200`);
    const j = await r.json();
    if (!j.success) { $tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#fca5a5">Ошибка: ${j.error || '?'}</td></tr>`; return; }

    document.getElementById('todayVisits').textContent = j.today_visits;
    document.getElementById('todayUsers').textContent = j.today_users;

    // Топ недели
    const $top = document.getElementById('topWeek');
    if (!j.top_week.length) $top.innerHTML = '<div style="color:#777; font-size:0.85rem;">Нет данных</div>';
    else $top.innerHTML = j.top_week.map((t, i) => `<div class="vs-top-item"><span>${i+1}. ${t.username}</span><span style="color:var(--accent,#7c3aed); font-weight:700">${t.visits}</span></div>`).join('');

    // Онлайн
    const $on = document.getElementById('onlineList');
    if (!j.online.length) $on.innerHTML = '<div style="color:#777; font-size:0.85rem;">Никого</div>';
    else $on.innerHTML = j.online.map(o => `<div class="vs-online-item"><span class="dot"></span><b>${o.username}</b> <span class="vs-pill ${o.role||'master'}">${o.role||''}</span><span style="margin-left:auto; color:#888; font-size:0.75rem">${fmtAgo(o.last_seen)}</span></div>`).join('');

    // Лента визитов
    if (!j.rows.length) { $tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#888; padding:1rem;">Нет визитов</td></tr>'; return; }
    $tbody.innerHTML = j.rows.map(v => `
        <tr>
            <td><b>${v.username}</b></td>
            <td><span class="vs-pill ${v.role||'master'}">${v.role || ''}</span></td>
            <td>${v.page}</td>
            <td><code style="color:#666; font-size:0.75rem">${v.ip || ''}</code></td>
            <td title="${v.visited_at}">${fmt(v.visited_at)}</td>
        </tr>
    `).join('');
}

document.getElementById('filterUser').addEventListener('input', () => { clearTimeout(window._tt); window._tt = setTimeout(reload, 300); });
document.getElementById('filterPage').addEventListener('input', () => { clearTimeout(window._tt); window._tt = setTimeout(reload, 300); });
reload();
setInterval(reload, 15000); // Авто-обновление каждые 15 сек
</script>
</body>
</html>
