<?php
session_start();
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'db.php';
require_once 'user_header.php';

$rules = include __DIR__ . '/rules_data.php';
$sectionsOrder = [];
foreach ($rules as $r) {
    if (!in_array($r['section'], $sectionsOrder, true)) $sectionsOrder[] = $r['section'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>FUTURAMA STAFF | Памятка</title>
<link rel="stylesheet" href="index.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .mg-wrap { max-width: 1100px; margin: 0 auto; }
    .mg-head { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
    .mg-head h1 { margin: 0; font-size: 1.6rem; font-weight: 800; }
    .mg-head .mg-sub { color: #888; font-size: 0.95rem; }

    .mg-searchbox {
        position: relative; margin-bottom: 1.5rem;
    }
    .mg-search {
        width: 100%; padding: 1rem 1.2rem 1rem 3rem; font-size: 1rem; color: #fff;
        background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
        border-radius: 14px; outline: none; transition: all 0.2s;
    }
    .mg-search:focus { border-color: var(--accent,#7c3aed); background: rgba(255,255,255,0.08); }
    .mg-search-icon {
        position: absolute; left: 1.1rem; top: 50%; transform: translateY(-50%);
        color: #888; font-size: 1.1rem; pointer-events: none;
    }

    .mg-meta {
        color: #777; font-size: 0.85rem; margin-bottom: 1rem;
    }

    .mg-section {
        margin-bottom: 1.5rem;
    }
    .mg-section-title {
        font-size: 0.78rem; font-weight: 800; letter-spacing: 0.08em;
        color: var(--accent,#7c3aed); text-transform: uppercase;
        margin: 0 0 0.6rem 0; padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    .mg-rule {
        background: rgba(20,20,30,0.55); border-radius: 12px;
        padding: 1rem 1.2rem; margin-bottom: 0.7rem;
        border-left: 3px solid #ef4444;
        transition: all 0.2s;
    }
    .mg-rule:hover { background: rgba(20,20,30,0.75); border-left-color: var(--accent,#7c3aed); }
    .mg-rule-head {
        display: flex; align-items: baseline; gap: 0.6rem; margin-bottom: 0.4rem;
    }
    .mg-rule-id {
        font-weight: 800; font-size: 0.95rem; color: #ef4444; font-family: monospace;
    }
    .mg-rule-title { font-weight: 700; color: #fff; font-size: 0.98rem; }
    .mg-rule-text { color: #ccc; font-size: 0.92rem; line-height: 1.5; }
    .mg-rule-notes {
        margin: 0.6rem 0 0; padding: 0.6rem 0.9rem;
        background: rgba(239,68,68,0.06); border-left: 2px solid #ef4444;
        border-radius: 6px; font-size: 0.88rem; color: #ddd; line-height: 1.5;
    }
    .mg-rule-notes ul { margin: 0; padding-left: 1rem; }
    .mg-rule-foot {
        display: flex; gap: 1.2rem; margin-top: 0.7rem;
        font-size: 0.85rem; color: #aaa;
    }
    .mg-rule-foot b { color: #fff; }
    .mg-rule-foot .pun { color: #fca5a5; }

    .mg-empty {
        text-align: center; color: #777; padding: 3rem 1rem; font-style: italic;
    }
    .mg-hl { background: rgba(124,58,237,0.35); border-radius: 3px; padding: 0 2px; color: #fff; font-weight: 700; }
</style>
</head>
<body>
<?php include 'sidebar_v2.php'; ?>
<main class="main-content">
    <div class="mg-wrap">
        <div class="mg-head">
            <h1><i class="fa-solid fa-book"></i> Памятка для мастеров</h1>
        </div>

        <div class="mg-searchbox">
            <i class="fa-solid fa-magnifying-glass mg-search-icon"></i>
            <input id="searchInput" class="mg-search" placeholder="Напр.: «можно ли мутить саппорта», «8 саппортов», «афк», «выйди из проходки»..." autofocus>
        </div>
        <div id="meta" class="mg-meta">Введи вопрос или ключевое слово — найду подходящее правило.</div>

        <div id="results"></div>
    </div>
</main>

<script>
    const RULES = <?= json_encode($rules, JSON_UNESCAPED_UNICODE) ?>;
    const SECTIONS = <?= json_encode($sectionsOrder, JSON_UNESCAPED_UNICODE) ?>;

    const $input = document.getElementById('searchInput');
    const $results = document.getElementById('results');
    const $meta = document.getElementById('meta');

    // === Стоп-слова: не учитываются как ключевые ===
    const STOPWORDS = new Set([
        'и','в','во','на','с','со','к','ко','от','до','из','за','для','о','об','же','то','тот','эта','это','эти',
        'я','мы','ты','вы','он','она','они','мне','меня','тебя','его','её','их','нам','вам','них','свой','свою','своя',
        'не','ни','но','а','или','либо','что','чтобы','когда','где','куда','откуда','как','такой','такая','такие',
        'быть','есть','был','была','были','если','можно','нельзя','надо','нужно','хочу','хочется',
        'правило','правил','правила','раздел','пункт'
    ]);

    function normalize(s) {
        return (s || '').toLowerCase().replace(/[ёе]/g, 'е').replace(/[^а-яa-z0-9\s]/gi, ' ').replace(/\s+/g, ' ').trim();
    }

    // Простой стеммер для русского + английского: отрезаем типичные окончания.
    // Не идеально, но для поиска по совпадению/префиксу работает хорошо.
    const RU_SUFFIXES = [
        'ями','ями','иями','ыми','ими','ого','его','ому','ему','ыми','ыми','ться','тся',
        'ами','ями','ами','ями','ные','ный','ная','ное','ние','ний','ает','ает','ает',
        'ать','ять','ить','еть','уть','ыть','ешь','ишь','ете','ите','ют','ят','ем','им',
        'ах','ях','ах','ях','ов','ев','ой','ей','ою','ею','ый','ий','ая','яя','ое','ее','ые','ие',
        'ам','ям','ом','ем','а','я','ы','и','у','ю','о','е','ь'
    ];
    function stem(w) {
        if (!w) return w;
        // оставляем как есть слишком короткое
        if (w.length <= 3) return w;
        for (const s of RU_SUFFIXES) {
            if (w.length > s.length + 2 && w.endsWith(s)) {
                return w.slice(0, -s.length);
            }
        }
        return w;
    }

    function tokenize(s) {
        const norm = normalize(s);
        return norm.split(' ')
            .filter(t => t.length >= 3)
            .filter(t => !STOPWORDS.has(t))
            .map(t => stem(t));
    }

    function highlight(text, terms) {
        if (!text) return '';
        let out = String(text).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        // подсвечиваем слова по корню (префиксу) — чтобы окрасить и «мут», и «мута», и «мутить»
        terms.forEach(t => {
            if (t.length < 3) return;
            const re = new RegExp('(' + t.replace(/[.*+?^${}()|[\]\\]/g,'\\$&') + '[а-яa-z]{0,6})', 'gi');
            out = out.replace(re, '<span class="mg-hl">$1</span>');
        });
        return out;
    }

    function score(rule, query) {
        if (!query) return 0;
        const tokens = tokenize(query);
        if (!tokens.length) return 0;

        // Заранее распарсенные «стеммированные» слова всех полей правила
        const fieldStems = {
            id:      [rule.id.toLowerCase()],
            title:   tokenize(rule.title),
            text:    tokenize(rule.text),
            notes:   tokenize((rule.notes || []).join(' ')),
            keys:    tokenize((rule.keywords || []).join(' ')),
            section: tokenize(rule.section || '')
        };
        // Сырые ключевые слова (для бонуса при точном совпадении ключа)
        const rawKeywords = (rule.keywords || []).map(k => normalize(k));

        let sc = 0;
        tokens.forEach(t => {
            // 1) точное совпадение по любому ключевому слову (после стемминга)
            if (fieldStems.keys.some(k => k === t)) sc += 14;
            // 2) частичное совпадение «корень есть в ключе» или наоборот
            if (fieldStems.keys.some(k => k.startsWith(t) || t.startsWith(k))) sc += 6;
            // 3) точное вхождение в сырое ключевое слово (для фраз вроде «8 саппортов»)
            if (rawKeywords.some(k => k.includes(t))) sc += 4;
            // 4) совпадение в заголовке
            if (fieldStems.title.some(w => w === t)) sc += 8;
            if (fieldStems.title.some(w => w.startsWith(t) || t.startsWith(w))) sc += 3;
            // 5) совпадение в id (например «4.7»)
            if (fieldStems.id.some(w => w.includes(t))) sc += 10;
            // 6) совпадение в тексте/заметках
            if (fieldStems.text.some(w => w === t)) sc += 4;
            if (fieldStems.notes.some(w => w === t)) sc += 3;
            if (fieldStems.text.some(w => w.startsWith(t) || t.startsWith(w))) sc += 2;
            if (fieldStems.notes.some(w => w.startsWith(t) || t.startsWith(w))) sc += 1;
            // 7) секция
            if (fieldStems.section.some(w => w.startsWith(t) || t.startsWith(w))) sc += 1;
        });
        return sc;
    }

    function renderRule(rule, queryTokens) {
        let html = '<div class="mg-rule">';
        html += `<div class="mg-rule-head"><span class="mg-rule-id">${rule.id}</span> <span class="mg-rule-title">${highlight(rule.title, queryTokens)}</span></div>`;
        html += `<div class="mg-rule-text">${highlight(rule.text, queryTokens)}</div>`;
        if (rule.notes && rule.notes.length) {
            html += '<div class="mg-rule-notes"><ul>';
            rule.notes.forEach(n => { html += `<li>${highlight(n, queryTokens)}</li>`; });
            html += '</ul></div>';
        }
        if (rule.punishment || rule.term) {
            html += '<div class="mg-rule-foot">';
            if (rule.punishment) html += `<div><b>Наказание:</b> <span class="pun">${rule.punishment}</span></div>`;
            if (rule.term) html += `<div><b>Срок:</b> ${rule.term}</div>`;
            html += '</div>';
        }
        html += '</div>';
        return html;
    }

    function render() {
        const q = $input.value.trim();
        const tokens = tokenize(q);

        if (!q) {
            // Без запроса — показываем всё, сгруппировано
            let html = '';
            SECTIONS.forEach(sec => {
                const list = RULES.filter(r => r.section === sec);
                if (!list.length) return;
                html += `<div class="mg-section"><div class="mg-section-title">${sec}</div>`;
                list.forEach(r => html += renderRule(r, []));
                html += '</div>';
            });
            $results.innerHTML = html;
            $meta.textContent = `Всего правил: ${RULES.length}`;
            return;
        }

        const scored = RULES.map(r => ({ r, s: score(r, q) })).filter(x => x.s > 0).sort((a, b) => b.s - a.s);

        if (!scored.length) {
            $results.innerHTML = '<div class="mg-empty">Ничего не нашёл. Попробуй другие слова: «мут», «АФК», «8 саппортов», «выйди».</div>';
            $meta.textContent = `По запросу «${q}» совпадений нет.`;
            return;
        }

        // Топ-15 результатов
        const top = scored.slice(0, 15);
        let html = '';
        top.forEach(x => html += renderRule(x.r, tokens));
        $results.innerHTML = html;
        $meta.textContent = `Найдено: ${scored.length} ${scored.length === 1 ? 'правило' : (scored.length < 5 ? 'правила' : 'правил')} (показываю топ ${Math.min(15, scored.length)})`;
    }

    $input.addEventListener('input', render);
    render();
</script>
</body>
</html>
