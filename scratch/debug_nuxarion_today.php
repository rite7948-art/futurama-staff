<?php
require_once 'db.php';
$id = '1337712604872704092';
echo "=== Проверка ВСЕХ данных для nuxarion ($id) ===\n";
$stmt = $pdo->prepare("SELECT * FROM voice_activity WHERE discord_id = ? ORDER BY start_time ASC");
$stmt->execute([$id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($rows as $row) {
    echo "ID: {$row['id']} | Начало: {$row['start_time']} | Конец: {$row['end_time']} | Длительность: {$row['duration']} сек.\n";
    $total += $row['duration'];
}

echo "--- Итого в базе за сегодня: " . round($total / 60, 2) . " мин. (" . $total . " сек.)\n";
