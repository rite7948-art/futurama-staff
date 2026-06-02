# CLAUDE.md — Корень проекта «Futurama Staff» (футик)

Этот файл описывает структуру и логику проекта для будущих сессий с Claude.

## Что это
Сайт управления стаффом Discord-сервера Futurama: PHP 8.2 + Apache (Docker), MySQL (PDO),
сессионная авторизация. Данные синхронизируются с Google Sheets (чтение через CSV-экспорт,
запись обратно через Google Apps Script webhook с токеном). Деплой на Railway.

## Архитектура / сервисы Railway
На Railway ДВА сервиса из одного репозитория:
1. **Сайт (PHP)** — собирается из `Dockerfile`.
2. **voice-tracker (селфбот)** — собирается из `Dockerfile.tracker` (`voice_tracker.js`).

> ВАЖНО: прод деплоится из удалённой ветки `main` (GitHub `moluzequvit928-star/-`).
> Локальная рабочая ветка — `master`. При добавлении НОВЫХ обязательных файлов (например
> `pet_functions.php`) их обязательно надо запушить, иначе весь сайт падает с фаталом.

## Ключевые файлы (PHP)
- `db.php` — PDO-подключение. **Автосоздаёт все таблицы при коннекте** (миграций нет):
  pets, pet_quests, pet_quest_progress, pet_achievements, staff_seen, staff_history,
  double_staff, voice_activity и др.
- `api.php` — единая точка API (action-роутер). Питомцы, достижения, квесты, переаттестация
  (`set_reattestation_result`), саппорты, `update_doublestaff`, `get_staff_ids`,
  `upload_media` (баннеры/обои). Хук начисления XP питомцу: +20 за переаттестацию, +15 за саппорт.
- `staff_functions.php` — чтение Google Sheets (CSV), `getAllDashboardData`, очередь
  переаттестаций. GID состава = `composition_gid` (отделён от `main_sheet_gid`=2053240546).
- `pet_functions.php` — система питомцев: `petTypes()` (эмодзи + Dota-герои через `dotaImg()`
  + енот + Shou Kusakabe), уровни/XP, квесты, лидерборд, `achievementDefs`, метрики.
- `pet.php` / `achievements.php` — страницы питомца и достижений (FA-иконки, без эмодзи).
- `settings.php` — настройки оформления: палитра акцента + «Свой цвет», шрифты (FONTS),
  обои (пресеты-градиенты / цвета / URL / загрузка / Ctrl+V), живой превью, «Сбросить всё».
- `user_header.php` — применяет localStorage (site_accent, site_wallpaper) на всём сайте.
- `sidebar_v2.php` — навигация + плавающий виджет питомца (учитывает `pet_hidden`).
- `login.php` / `discord_login.php` — вход (логин/пароль + Discord OAuth2 `identify`).
- `lobby_check.php` — статистика проходок (источник — таблица `voice_activity` по `start_time`).
- `double_staff.php` / `staff_history.php` — пока заглушки «Раздел в разработке».

## Ключевые файлы (Node / боты)
- `voice_tracker.js` — **селфбот** (`discord.js-selfbot-v13`): учёт голосовой активности +
  фоновый скан дабл-стаффа (`runDoubleStaffScan`, постит в `update_doublestaff`).
- `check_sync.js` — сверка таблиц. Наличие роли подтверждается **реальным ботом** через REST
  (`GET /guilds/{guild}/members/{user}`, токен `DISCORD_TOKEN`), загрузка участников
  последовательная, не Promise.all.
- `bot.js` — реальный бот (`discord.js`).
- `doublestaff_checker.js` — отдельный чекер (см. `Dockerfile.doublestaff`).

## Конфигурация
`configValue($envName, $configKey, $default)`: сначала env-переменная, затем ключ из
`app_config.php`, затем дефолт. ВНИМАНИЕ: пустое значение ('') в конфиге возвращается вместо
дефолта.

## СЕКРЕТЫ — не коммитить (в .gitignore)
`app_config.php`, `bot_config.json`, `.env`, `users.json`. В `users.json` лежат логины/пароли
в открытом виде. `set_admin_pass.php` — утилита смены пароля (содержит хардкод, держать приватным).

## Особенности / подводные камни
- Google Sheets валидация: колонка H строго «сдал»/«Не сдал»/«-»; колонка I «прошел»/«1/3»/«2/3»/«3/3»/«-».
- Discord CDN attachment URL'ы истекают (~24ч) — картинки питомцев лучше хостить на стабильном CDN.
- Railway — эфемерная ФС; баннеры/обои в `uploads/` требуют Volume для сохранности между деплоями.
- Селфбот не может надёжно тянуть участников гильдии на 133k — для дабл-стаффа используется
  список стаффа с сайта + точечная проверка по ID.
