require('dotenv').config();
const { Client } = require('discord.js-selfbot-v13');
const axios = require('axios');

const client = new Client({ checkUpdate: false });

const GUILD_ID = process.env.GUILD_ID;                 // основной сервер
const SUPPORT_ROLE_ID = process.env.ROLE_ID || '993871290161172480'; // роль "саппорт" на основном сервере

const API_BASE = process.env.SITE_URL || 'http://127.0.0.1:8000';
const API_URL = `${API_BASE}/api.php?action=update_doublestaff`;
const API_TOKEN = process.env.BOT_API_TOKEN || 'futika_bot_secret_2026';

// Как часто пересканировать (мс). По умолчанию раз в час.
const SCAN_INTERVAL = parseInt(process.env.DS_INTERVAL_MS || '3600000', 10);

// Ключевые слова "стафовых" ролей (нижний регистр, частичное совпадение)
const STAFF_KEYWORDS = [
    'саппорт', 'support', 'саппорты',
    'модер', 'moder', 'moderator', 'модератор',
    'контрол', 'control',
    'админ', 'admin', 'administrator', 'администратор',
    'куратор', 'curator',
    'staff', 'стафф', 'хелпер', 'helper',
    'гл.', 'хелп'
];

function isStaffRole(name) {
    const n = (name || '').toLowerCase();
    return STAFF_KEYWORDS.some(k => n.includes(k));
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

async function runScan() {
    try {
        const mainGuild = client.guilds.cache.get(GUILD_ID);
        if (!mainGuild) { console.error('❌ Основной сервер не найден'); return; }

        // 1) Собираем стаф основного сервера (по роли саппорта)
        console.log('⏳ Загружаю участников основного сервера...');
        await mainGuild.members.fetch();
        const staff = new Map(); // id -> username
        mainGuild.members.cache.forEach(m => {
            if (m.roles.cache.has(SUPPORT_ROLE_ID)) {
                staff.set(m.id, m.user.username);
            }
        });
        console.log(`👥 Стафа на основном сервере: ${staff.size}`);
        if (staff.size === 0) return;

        // results: id -> { username, entries: [{guild, role}] }
        const found = new Map();

        // 2) Проходим по всем ОСТАЛЬНЫМ серверам, где есть аккаунт
        const otherGuilds = client.guilds.cache.filter(g => g.id !== GUILD_ID);
        console.log(`🌐 Проверяю ${otherGuilds.size} других серверов...`);

        for (const [, guild] of otherGuilds) {
            try {
                await guild.members.fetch();
            } catch (e) {
                console.warn(`⚠️ Не удалось загрузить участников "${guild.name}": ${e.message}`);
                continue;
            }

            for (const [id, username] of staff) {
                const member = guild.members.cache.get(id);
                if (!member) continue;
                const staffRoles = member.roles.cache
                    .filter(r => r.name !== '@everyone' && isStaffRole(r.name))
                    .map(r => r.name);
                if (staffRoles.length > 0) {
                    if (!found.has(id)) found.set(id, { discord_id: id, username, entries: [] });
                    staffRoles.forEach(rn => found.get(id).entries.push({ guild: guild.name, role: rn }));
                }
            }
            await sleep(1500); // пауза, чтобы не словить рейт-лимит
        }

        const results = Array.from(found.values());
        console.log(`🔎 Найдено дабл-стаффов: ${results.length}`);

        // 3) Отправляем на сайт
        await axios.post(API_URL, { token: API_TOKEN, results });
        console.log('✅ Результаты отправлены на сайт');
    } catch (err) {
        console.error('❌ Ошибка сканирования:', err.message);
    }
}

client.on('ready', async () => {
    console.log(`✅ Double-Staff Checker запущен как ${client.user.tag}`);

    // Список всех серверов, где состоит аккаунт (что чекер может сканировать)
    console.log(`📋 Аккаунт состоит в ${client.guilds.cache.size} серверах:`);
    client.guilds.cache
        .sort((a, b) => b.memberCount - a.memberCount)
        .forEach(g => console.log(`   • ${g.name} (${g.memberCount} участников) [${g.id}]`));

    await runScan();
    setInterval(runScan, SCAN_INTERVAL);
});

client.login(process.env.Self_bot);
