/**
 * sync_checker.js — v7
 * Перебираем ВСЕ доступные Discord endpoints для получения участников с ролью.
 */

const https = require('https');

const USER_TOKEN = process.env.Self_bot || ''; // токен из env, не коммитить в код!
const GUILD_ID   = '531970658633252864';
const ROLE_ID    = '993871290161172480';
const SHEET_ID   = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
const SHEET_GID  = '1970062457';

const H = {
  'Authorization' : USER_TOKEN,
  'User-Agent'    : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
  'Accept'        : 'application/json',
  'X-Discord-Locale': 'ru',
};

const sleep = ms => new Promise(r => setTimeout(r, ms));

function req(url) {
  return new Promise((resolve, reject) => {
    const p = new URL(url);
    https.request({ hostname: p.hostname, path: p.pathname + p.search, headers: H }, res => {
      let d = '';
      res.on('data', c => d += c);
      res.on('end', () => {
        try { resolve({ s: res.statusCode, b: JSON.parse(d) }); }
        catch { resolve({ s: res.statusCode, b: d }); }
      });
    }).on('error', reject).end();
  });
}

function fetchText(url) {
  return new Promise((resolve, reject) => {
    const go = (u, n = 0) => {
      if (n > 8) return reject(new Error('redirects'));
      const p = new URL(u);
      https.get({ hostname: p.hostname, path: p.pathname + p.search, headers: { 'User-Agent': 'Mozilla/5.0' } }, res => {
        if (res.statusCode >= 300 && res.headers.location) return go(res.headers.location, n + 1);
        let d = ''; res.on('data', c => d += c); res.on('end', () => resolve(d));
      }).on('error', reject);
    };
    go(url);
  });
}

async function getSheetIds() {
  console.log('📊 Google Sheets...');
  const csv = await fetchText(`https://docs.google.com/spreadsheets/d/${SHEET_ID}/export?format=csv&gid=${SHEET_GID}`);
  const ids = new Set();
  for (const line of csv.split('\n')) {
    const c = line.split(',').map(x => x.trim().replace(/^"|"$/g, ''));
    if (c[3] && /^\d{15,20}$/.test(c[3])) ids.add(c[3]);
  }
  console.log(`   → ${ids.size} ID в таблице\n`);
  return ids;
}

async function main() {
  console.log('\n🚀 Сканирование роли Саппорт\n');

  // Тест токена
  const me = await req('https://discord.com/api/v9/users/@me');
  if (me.s !== 200) { console.error('❌ Токен:', me.b); process.exit(1); }
  console.log(`✅ Аккаунт: ${me.b.username}\n`);

  const withRole = new Map();

  // ── Метод 1: GET /roles/{role_id}/members (API v10) ─────────────────────
  console.log('🔍 Метод 1: /guilds/.../roles/.../members ...');
  const r1 = await req(`https://discord.com/api/v10/guilds/${GUILD_ID}/roles/${ROLE_ID}/members?limit=1000`);
  console.log(`   Статус: ${r1.s}`);
  if (r1.s === 200 && Array.isArray(r1.b)) {
    for (const m of r1.b) withRole.set(m.user.id, { id: m.user.id, username: m.user.username, nick: m.nick || m.user.global_name || m.user.username });
    console.log(`   ✅ Получено ${r1.b.length} участников!`);
  } else {
    console.log(`   ❌ Не сработало: ${JSON.stringify(r1.b).slice(0, 120)}`);
  }

  // ── Метод 2: Поиск участников (search) ───────────────────────────────────
  if (withRole.size === 0) {
    console.log('\n🔍 Метод 2: /members/search ...');
    // Ищем по буквам алфавита чтобы охватить всех
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789_абвгдежзийклмнопрстуфхцчшщъыьэюя';
    for (const ch of chars) {
      const r = await req(`https://discord.com/api/v9/guilds/${GUILD_ID}/members/search?query=${encodeURIComponent(ch)}&limit=1000`);
      if (r.s !== 200) continue;
      for (const m of r.b) {
        if (m.roles?.includes(ROLE_ID)) {
          withRole.set(m.user.id, { id: m.user.id, username: m.user.username, nick: m.nick || m.user.global_name || m.user.username });
        }
      }
      process.stdout.write(`\r   → поиск '${ch}' | найдено с ролью: ${withRole.size}   `);
      await sleep(250);
    }
    console.log('');
    if (withRole.size > 0) console.log(`   ✅ Найдено ${withRole.size} участников с ролью!`);
    else console.log('   ❌ Search тоже не дал результатов.');
  }

  // ── Метод 3: /guilds/{id}/members (с правами?) ───────────────────────────
  if (withRole.size === 0) {
    console.log('\n🔍 Метод 3: /members pagination ...');
    let after = '0', page = 0;
    while (true) {
      const r = await req(`https://discord.com/api/v9/guilds/${GUILD_ID}/members?limit=1000&after=${after}`);
      if (r.s !== 200) { console.log(`   ❌ Статус ${r.s}: ${JSON.stringify(r.b).slice(0,100)}`); break; }
      const batch = r.b;
      if (!Array.isArray(batch) || !batch.length) break;
      for (const m of batch) {
        if (m.roles?.includes(ROLE_ID)) withRole.set(m.user.id, { id: m.user.id, username: m.user.username, nick: m.nick || m.user.global_name || m.user.username });
      }
      page++;
      process.stdout.write(`\r   Страница ${page} | всего: ${batch.length} | с ролью: ${withRole.size}   `);
      if (batch.length < 1000) break;
      after = batch[batch.length - 1].user.id;
      await sleep(300);
    }
    console.log('');
  }

  // ── Итог ──────────────────────────────────────────────────────────────────
  const sheetIds = await getSheetIds();

  if (withRole.size === 0) {
    console.log('\n❌ Ни один метод не вернул участников с ролью.');
    console.log('   Discord технически блокирует получение этого списка для данного аккаунта.');
    console.log('   Нужен бот с Server Members Intent на сервере.\n');
    return;
  }

  const withRoleSet = new Set(withRole.keys());
  const inDiscordNotSheet = [...withRole.values()].filter(m => !sheetIds.has(m.id));
  const inSheetNoRole = [...sheetIds].filter(id => !withRoleSet.has(id));

  const L = '═'.repeat(68);
  console.log('\n' + L);
  console.log('📊  РЕЗУЛЬТАТЫ СВЕРКИ');
  console.log(L);
  console.log(`   С ролью Саппорт в Discord : ${withRole.size}`);
  console.log(`   ID в Google Таблице        : ${sheetIds.size}`);
  console.log(L);

  console.log(`\n🔴 РОЛЬ ЕСТЬ, в таблице НЕТ (${inDiscordNotSheet.length}):`);
  if (!inDiscordNotSheet.length) console.log('   ✅ Все с ролью есть в таблице');
  else inDiscordNotSheet.forEach(m => console.log(`   • ${(m.nick||m.username).padEnd(34)} │ ID: ${m.id}`));

  console.log(`\n🟡 В ТАБЛИЦЕ ЕСТЬ, роли НЕТ (${inSheetNoRole.length}):`);
  if (!inSheetNoRole.length) console.log('   ✅ Все из таблицы имеют роль');
  else inSheetNoRole.forEach(id => console.log(`   • ID: ${id}`));

  console.log('\n' + L + '\n✅ Готово!\n' + L + '\n');
}

main().catch(e => { console.error('❌', e.message); process.exit(1); });
