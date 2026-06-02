<?php
require_once 'db.php';
require_once 'staff_functions.php';

// 1. Получаем список из Google Таблицы
$csvUrl = getGoogleSheetCsvUrl(configValue('MAIN_SHEET_GID', 'main_sheet_gid', '1970062457'));
$rows = loadCsvRows($csvUrl);

echo "=== Проверка ID из Google Таблицы ===\n";
$sheet_members = [];
foreach ($rows as $index => $row) {
    if ($index < 2) continue;
    $nick = trim($row[2] ?? '');
    $id = preg_replace('/[^0-9]/', '', (string)($row[3] ?? ''));
    if ($nick && $id && $nick !== '-') {
        $sheet_members[$id] = $nick;
        if (strpos(strtolower($nick), 'nuxarion') !== false) {
            echo "НАЙДЕН NUXARION в таблице: Nick=$nick, ID=$id\n";
        }
    }
}

// 2. Получаем статистику из базы за неделю
$monday = date('Y-m-d 00:00:00', strtotime('monday this week'));
$stmt = $pdo->prepare("SELECT discord_id, SUM(duration) as total FROM voice_activity WHERE start_time >= ? GROUP BY discord_id");
$stmt->execute([$monday]);
$db_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== Статистика в базе данных (ID -> Время) ===\n";
foreach ($db_stats as $stat) {
    $id = $stat['discord_id'];
    $time = round($stat['total'] / 60, 1) . " мин.";
    $nick = $sheet_members[$id] ?? "??? (Нет в таблице)";
    echo "ID: $id | Nick: $nick | Время: $time\n";
}
