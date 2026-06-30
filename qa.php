<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
require_once 'user_header.php';
$role = $_SESSION['role'] ?? 'master';
$isAdmin = in_array($role, ['admin','chief','curator'], true);
$myUser = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>FUTURAMA STAFF | Ответы на вопросы</title>
<link rel="stylesheet" href="index.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .qa-wrap { max-width: 1000px; margin: 0 auto; }
    .qa-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .qa-head h1 { margin: 0; font-size: 1.5rem; font-weight: 800; }

    .qa-ask {
        background: rgba(124,58,237,0.08); border: 1px solid rgba(124,58,237,0.25);
        border-radius: 14px; padding: 1.2rem; margin-bottom: 1.5rem;
    }
    .qa-ask h3 { margin: 0 0 0.7rem; font-size: 1rem; color: var(--accent,#7c3aed); }
    .qa-ask textarea {
        width: 100%; min-height: 80px; resize: vertical; padding: 0.8rem;
        background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 10px; color: #fff; font-family: inherit; font-size: 0.95rem;
    }
    .qa-ask textarea:focus { outline: none; border-color: var(--accent,#7c3aed); background: rgba(255,255,255,0.07); }
    .qa-ask button {
        margin-top: 0.7rem; padding: 0.7rem 1.4rem; font-weight: 700;
        background: var(--accent,#7c3aed); color: #fff; border: none; border-radius: 10px;
        cursor: pointer; font-size: 0.95rem;
    }
    .qa-ask button:hover { filter: brightness(1.1); }
    .qa-ask button:disabled { opacity: 0.5; cursor: not-allowed; }

    .qa-search {
        width: 100%; padding: 0.8rem 1rem 0.8rem 2.8rem; font-size: 0.95rem; color: #fff;
        background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 12px; outline: none; margin-bottom: 1rem;
    }
    .qa-search-wrap { position: relative; }
    .qa-search-icon {
        position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #888; pointer-events: none;
    }

    .qa-tabs { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
    .qa-tab {
        padding: 0.4rem 0.9rem; border-radius: 20px; background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.08); cursor: pointer; font-size: 0.85rem; color: #ccc;
    }
    .qa-tab.active { background: var(--accent,#7c3aed); color: #fff; border-color: transparent; }
    .qa-tab .cnt { opacity: 0.75; margin-left: 0.3rem; }

    .qa-item {
        background: rgba(20,20,30,0.55); border-radius: 12px;
        padding: 1rem 1.2rem; margin-bottom: 0.8rem;
        border-left: 3px solid #f59e0b;
    }
    .qa-item.answered { border-left-color: #10b981; }
    .qa-q { font-weight: 700; color: #fff; font-size: 1rem; margin-bottom: 0.4rem; }
    .qa-meta { color: #888; font-size: 0.78rem; margin-bottom: 0.5rem; display: flex; gap: 0.7rem; flex-wrap: wrap; align-items: center; }
    .qa-meta .qa-pill {
        display: inline-block; padding: 0.15rem 0.5rem; border-radius: 12px; font-weight: 700; font-size: 0.7rem;
    }
    .qa-meta .qa-pill.pending { background: #f59e0b; color: #000; }
    .qa-meta .qa-pill.answered { background: #10b981; color: #fff; }
    .qa-answer {
        margin-top: 0.6rem; padding: 0.7rem 0.9rem;
        background: rgba(16,185,129,0.06); border-left: 2px solid #10b981;
        border-radius: 6px; color: #ddd; font-size: 0.92rem; line-height: 1.5;
    }
    .qa-answer .qa-by { color: #888; font-size: 0.75rem; margin-top: 0.4rem; }

    .qa-actions { display: flex; gap: 0.4rem; margin-top: 0.5rem; }
    .qa-btn {
        padding: 0.35rem 0.8rem; border-radius: 8px; cursor: pointer; font-size: 0.8rem;
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); color: #ccc;
    }
    .qa-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
    .qa-btn.danger:hover { background: rgba(239,68,68,0.2); color: #fca5a5; }
    .qa-btn.primary { background: var(--accent,#7c3aed); color: #fff; border-color: transparent; }

    .qa-answer-form { margin-top: 0.6rem; display: none; }
    .qa-answer-form.show { display: block; }
    .qa-answer-form textarea {
        width: 100%; min-height: 60px; padding: 0.6rem; resize: vertical;
        background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 8px; color: #fff; font-family: inherit; font-size: 0.9rem;
    }
    .qa-answer-form textarea:focus { outline: none; border-color: var(--accent,#7c3aed); }

    .qa-empty { text-align: center; color: #777; padding: 3rem 1rem; font-style: italic; }
</style>
</head>
<body>
<?php include 'sidebar_v2.php'; ?>
<main class="main-content">
    <div class="qa-wrap">
        <div class="qa-head">
            <h1><i class="fa-solid fa-circle-question"></i> Ответы на вопросы</h1>
        </div>

        <div class="qa-ask">
            <h3><i class="fa-solid fa-pen"></i> Задать свой вопрос</h3>
            <textarea id="askInput" placeholder="Напиши вопрос. Например: «Можно ли мутить ключика без причины?» или «Что делать если новичок матерится?»"></textarea>
            <button id="askBtn"><i class="fa-solid fa-paper-plane"></i> Отправить вопрос</button>
            <div id="askStatus" style="margin-top:0.5rem; font-size:0.85rem; color:#888;"></div>
        </div>

        <div class="qa-search-wrap">
            <i class="fa-solid fa-magnifying-glass qa-search-icon"></i>
            <input id="qaSearch" class="qa-search" placeholder="Поиск по вопросам и ответам...">
        </div>

        <div class="qa-tabs">
            <div class="qa-tab active" data-f="all">Все <span class="cnt" id="cntAll">0</span></div>
            <div class="qa-tab" data-f="pending">Без ответа <span class="cnt" id="cntPending">0</span></div>
            <div class="qa-tab" data-f="answered">Отвечено <span class="cnt" id="cntAnswered">0</span></div>
        </div>

        <div id="qaList"></div>
    </div>
</main>

<script>
const IS_ADMIN = <?= $isAdmin ? 'true' : 'false' ?>;
const MY_USER = <?= json_encode($myUser, JSON_UNESCAPED_UNICODE) ?>;

const $list = document.getElementById('qaList');
const $search = document.getElementById('qaSearch');
let allRows = [];
let filterMode = 'all';

function fmtDate(t) {
    if (!t) return '';
    const d = new Date(t.replace(' ', 'T') + 'Z');
    return d.toLocaleString('ru-RU', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
}
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function nl2br(s) { return esc(s).replace(/\n/g, '<br>'); }

function render() {
    const q = ($search.value || '').toLowerCase().trim();
    let rows = allRows.filter(r => {
        if (filterMode === 'pending' && r.status !== 'pending') return false;
        if (filterMode === 'answered' && r.status !== 'answered') return false;
        if (q) {
            const blob = ((r.question||'') + ' ' + (r.answer||'') + ' ' + (r.asker_username||'')).toLowerCase();
            if (!blob.includes(q)) return false;
        }
        return true;
    });

    document.getElementById('cntAll').textContent = allRows.length;
    document.getElementById('cntPending').textContent = allRows.filter(r => r.status === 'pending').length;
    document.getElementById('cntAnswered').textContent = allRows.filter(r => r.status === 'answered').length;

    if (!rows.length) {
        $list.innerHTML = '<div class="qa-empty">Вопросов нет. Будь первым — задай свой выше!</div>';
        return;
    }

    let html = '';
    rows.forEach(r => {
        const statusPill = r.status === 'answered'
            ? '<span class="qa-pill answered">Отвечено</span>'
            : '<span class="qa-pill pending">Без ответа</span>';
        html += `<div class="qa-item ${r.status}" id="qa-${r.id}">`;
        html += `<div class="qa-q">${nl2br(r.question)}</div>`;
        html += `<div class="qa-meta">`;
        html += `${statusPill}`;
        html += `<span><i class="fa-regular fa-user"></i> ${esc(r.asker_username)}</span>`;
        html += `<span><i class="fa-regular fa-clock"></i> ${fmtDate(r.created_at)}</span>`;
        html += `</div>`;

        if (r.status === 'answered' && r.answer) {
            html += `<div class="qa-answer">${nl2br(r.answer)}<div class="qa-by">— ${esc(r.answered_by || '')} · ${fmtDate(r.answered_at)}</div></div>`;
        }

        // Кнопки
        const canDelete = IS_ADMIN || r.asker_username === MY_USER;
        const canAnswer = IS_ADMIN && r.status === 'pending';
        if (canAnswer || canDelete) {
            html += `<div class="qa-actions">`;
            if (canAnswer) html += `<button class="qa-btn primary" onclick="toggleAnswerForm(${r.id})"><i class="fa-solid fa-reply"></i> Ответить</button>`;
            if (canDelete) html += `<button class="qa-btn danger" onclick="deleteQuestion(${r.id})"><i class="fa-regular fa-trash-can"></i> Удалить</button>`;
            html += `</div>`;
        }

        if (canAnswer) {
            html += `<div class="qa-answer-form" id="form-${r.id}">`;
            html += `<textarea id="ans-${r.id}" placeholder="Введи ответ..."></textarea>`;
            html += `<button class="qa-btn primary" style="margin-top:0.4rem" onclick="submitAnswer(${r.id})"><i class="fa-solid fa-check"></i> Опубликовать</button>`;
            html += `</div>`;
        }

        html += `</div>`;
    });
    $list.innerHTML = html;
}

async function loadList() {
    try {
        const r = await fetch('api.php?action=qa_list');
        const j = await r.json();
        if (!j.success) { $list.innerHTML = '<div class="qa-empty">Ошибка загрузки: ' + (j.error||'?') + '</div>'; return; }
        allRows = j.rows || [];
        render();
    } catch (e) {
        $list.innerHTML = '<div class="qa-empty">Сеть: ' + e.message + '</div>';
    }
}

document.getElementById('askBtn').addEventListener('click', async () => {
    const q = document.getElementById('askInput').value.trim();
    const $st = document.getElementById('askStatus');
    if (q.length < 5) { $st.textContent = 'Слишком короткий вопрос.'; $st.style.color = '#fca5a5'; return; }
    document.getElementById('askBtn').disabled = true;
    $st.style.color = '#888'; $st.textContent = 'Отправляю...';
    try {
        const r = await fetch('api.php?action=qa_ask', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ question: q })
        });
        const j = await r.json();
        if (!j.success) { $st.textContent = 'Ошибка: ' + (j.error||'?'); $st.style.color = '#fca5a5'; }
        else {
            $st.textContent = 'Вопрос отправлен. Спасибо!';
            $st.style.color = '#10b981';
            document.getElementById('askInput').value = '';
            await loadList();
        }
    } catch (e) {
        $st.textContent = 'Сеть: ' + e.message; $st.style.color = '#fca5a5';
    }
    document.getElementById('askBtn').disabled = false;
});

function toggleAnswerForm(id) {
    document.getElementById('form-' + id)?.classList.toggle('show');
}

async function submitAnswer(id) {
    const txt = document.getElementById('ans-' + id).value.trim();
    if (txt.length < 2) { alert('Ответ слишком короткий'); return; }
    const r = await fetch('api.php?action=qa_answer', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id, answer: txt })
    });
    const j = await r.json();
    if (!j.success) { alert('Ошибка: ' + (j.error||'?')); return; }
    await loadList();
}

async function deleteQuestion(id) {
    if (!confirm('Удалить вопрос?')) return;
    const r = await fetch('api.php?action=qa_delete', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id })
    });
    const j = await r.json();
    if (!j.success) { alert('Ошибка: ' + (j.error||'?')); return; }
    await loadList();
}

document.querySelectorAll('.qa-tab').forEach(t => {
    t.addEventListener('click', () => {
        document.querySelectorAll('.qa-tab').forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        filterMode = t.dataset.f;
        render();
    });
});
$search.addEventListener('input', render);

loadList();
</script>
</body>
</html>
