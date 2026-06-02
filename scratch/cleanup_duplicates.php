<?php
require_once 'db.php';

echo "Cleaning up duplicate voice logs...\n";

// Находим дубликаты по (discord_id, end_time)
$stmt = $pdo->query("
    SELECT discord_id, end_time, COUNT(*) as cnt, MIN(id) as keep_id
    FROM voice_activity
    GROUP BY discord_id, end_time
    HAVING cnt > 1
");

$duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalRemoved = 0;

foreach ($duplicates as $dup) {
    $stmtDel = $pdo->prepare("DELETE FROM voice_activity WHERE discord_id = ? AND end_time = ? AND id != ?");
    $stmtDel->execute([$dup['discord_id'], $dup['end_time'], $dup['keep_id']]);
    $removed = $dup['cnt'] - 1;
    $totalRemoved += $removed;
    echo "Removed $removed duplicates for User {$dup['discord_id']} at {$dup['end_time']}\n";
}

echo "\nDone! Total removed: $totalRemoved records.\n";
