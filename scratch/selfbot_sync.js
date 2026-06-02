/**
 * 🚀 SELF-BOT SYNC (Version: Absolute Scratch)
 * 
 * Сверяет участников с ролью Support в Discord и список ID из Google Таблицы.
 */

const https = require('https');

// --- НАСТРОЙКИ ---
const TOKEN    = process.env.Self_bot || ''; // токен из env, не коммитить в код!
const GUILD_ID = '531970658633252864';
const ROLE_ID  = '993871290161172480';
const SHEET_ID = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
const GID      = '1970062457';
// -----------------

const HEADERS = {
    'Authorization': TOKEN,
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    'Content-Type': 'application/json'
};

/** Функция для выполнения HTTP-запросов */
function request(url, options = {}) {
    return new Promise((resolve, reject) => {
        const req = https.request(url, { ...options, headers: { ...HEADERS, ...options.headers } }, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    const json = data ? JSON.parse(data) : {};
                    resolve({ status: res.statusCode, body: json });
                } catch (e) {
                    resolve({ status: res.statusCode, body: data });
                }
            });
        });
        req.on('error', reject);
        if (options.body) req.write(JSON.stringify(options.body));
        req.end();
    });
}

/** Получение данных из Google Sheets */
async function getTableIds() {
    console.log('📥 Загружаю данные из Google Таблицы...');
    const url = `https://docs.google.com/spreadsheets/d/${SHEET_ID}/export?format=csv&gid=${GID}`;
    return new Promise((resolve, reject) => {
        https.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                const ids = new Set();
                const lines = data.split('\n');
                lines.forEach(line => {
                    const columns = line.split(',');
                    const id = columns[3]?.trim().replace(/"/g, ''); // Столбец D
                    if (id && /^\d{17,20}$/.test(id)) {
                        ids.add(id);
                    }
                });
                resolve(ids);
            });
        }).on('error', reject);
    });
}

/** Получение участников с ролью через Discord API */
async function getDiscordMembers() {
    console.log('📡 Сканирую участников сервера Discord...');
    const membersWithRole = new Map(); // ID -> Username/Nick
    let after = '0';
    let hasMore = true;

    while (hasMore) {
        const res = await request(`https://discord.com/api/v9/guilds/${GUILD_ID}/members?limit=1000&after=${after}`);
        
        if (res.status === 403) {
            console.error('❌ Ошибка 403: Доступ запрещен. Похоже, у аккаунта нет прав "Управление сервером" или сервер слишком большой.');
            return null;
        }
        if (res.status === 429) {
            const wait = (res.body.retry_after || 1) * 1000;
            console.log(`⏳ Лимит запросов. Ждем ${wait/1000} сек...`);
            await new Promise(r => setTimeout(r, wait));
            continue;
        }
        if (res.status !== 200) {
            console.error(`❌ Ошибка API (${res.status}):`, res.body);
            return null;
        }

        const batch = res.body;
        if (!batch.length) {
            hasMore = false;
            break;
        }

        batch.forEach(m => {
            if (m.roles.includes(ROLE_ID)) {
                const name = m.nick || m.user.global_name || m.user.username;
                membersWithRole.set(m.user.id, name);
            }
            after = m.user.id;
        });

        console.log(`   ...проверено ${batch.length} участников (всего с ролью найдено: ${membersWithRole.size})`);
        
        if (batch.length < 1000) hasMore = false;
        await new Promise(r => setTimeout(r, 500)); // Защита от спама
    }

    return membersWithRole;
}

/** Основная логика */
async function sync() {
    console.log('--- ЗАПУСК СВЕРКИ ---');
    
    const [tableIds, discordMembers] = await Promise.all([
        getTableIds(),
        getDiscordMembers()
    ]);

    if (!discordMembers) {
        console.log('\n🛑 Не удалось получить список из Discord. Сверка невозможна.');
        return;
    }

    console.log('\n' + '='.repeat(40));
    console.log(`📊 ИТОГИ:`);
    console.log(`   В таблице: ${tableIds.size} чел.`);
    console.log(`   В Discord с ролью: ${discordMembers.size} чел.`);
    console.log('='.repeat(40) + '\n');

    // 1. Кто есть в таблице, но нет роли
    console.log('🟡 ЕСТЬ В ТАБЛИЦЕ, НО НЕТ РОЛИ САППОРТА:');
    let count1 = 0;
    tableIds.forEach(id => {
        if (!discordMembers.has(id)) {
            console.log(`   - ID: ${id}`);
            count1++;
        }
    });
    if (count1 === 0) console.log('   ✅ Все люди из таблицы имеют роль.');

    console.log('\n' + '-'.repeat(40) + '\n');

    // 2. Кто имеет роль, но его нет в таблице
    console.log('🔴 ЕСТЬ РОЛЬ, НО НЕТ В ТАБЛИЦЕ:');
    let count2 = 0;
    discordMembers.forEach((name, id) => {
        if (!tableIds.has(id)) {
            console.log(`   - ${name} (ID: ${id})`);
            count2++;
        }
    });
    if (count2 === 0) console.log('   ✅ Все люди с ролью занесены в таблицу.');

    console.log('\n' + '='.repeat(40));
    console.log('✅ СВЕРКА ЗАВЕРШЕНА');
    console.log('='.repeat(40));
}

sync().catch(console.error);
