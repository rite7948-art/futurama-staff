require('dotenv').config();
const { Client } = require('discord.js-selfbot-v13');
const client = new Client({ checkUpdate: false });

client.on('ready', async () => {
    console.log(`✅ Залогинился как ${client.user.tag}`);
    const guild = client.guilds.cache.get(process.env.GUILD_ID);
    if (!guild) {
        console.error('❌ Сервер не найден!');
        process.exit(1);
    }

    console.log('\n--- СПИСОК РОЛЕЙ СЕРВЕРА ---');
    guild.roles.cache.forEach(role => {
        console.log(`${role.name.padEnd(30)} | ID: ${role.id}`);
    });

    console.log('\n--- ПРОВЕРКА ПОЛЬЗОВАТЕЛЯ ZeXeeZ (zexezzz) ---');
    try {
        // Попробуем найти его по нику или ID
        const members = await guild.members.search({ query: 'zexezzz', limit: 1 });
        const member = members.first();
        
        if (member) {
            console.log(`Пользователь найден: ${member.user.tag} (${member.id})`);
            console.log('Его роли:');
            member.roles.cache.forEach(r => console.log(` - ${r.name} (${r.id})`));
            
            const targetRoleId = process.env.ROLE_ID;
            if (member.roles.cache.has(targetRoleId)) {
                console.log(`\n✅ У него ЕСТЬ роль с ID ${targetRoleId}`);
            } else {
                console.log(`\n❌ У него НЕТ роли с ID ${targetRoleId}`);
            }
        } else {
            console.log('❌ Пользователь zexezzz не найден через поиск.');
        }
    } catch (e) {
        console.error('Ошибка при поиске:', e.message);
    }

    process.exit(0);
});

client.login(process.env.TOKEN);
