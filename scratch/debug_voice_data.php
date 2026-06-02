<?php
require_once 'db.php';
$monday = date('Y-m-d 00:00:00', strtotime('monday this week'));
echo "Monday starts at: $monday\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";

$stmt = $pdo->prepare("
    SELECT 
        discord_id, 
        start_time,
        duration,
        DATE(start_time) as activity_date
    FROM voice_activity 
    WHERE start_time >= ?
    ORDER BY start_time ASC
");
$stmt->execute([$monday]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totals = [];
foreach ($rows as $row) {
    $id = $row['discord_id'];
    if (!isset($totals[$id])) $totals[$id] = ['total' => 0, 'days' => []];
    $totals[$id]['total'] += $row['duration'];
    $date = $row['activity_date'];
    if (!isset($totals[$id]['days'][$date])) $totals[$id]['days'][$date] = 0;
    $totals[$id]['days'][$date] += $row['duration'];
    
    echo "ID: {$row['discord_id']} | Date: {$row['start_time']} | Dur: " . round($row['duration']/60, 1) . " min\n";
}

echo "\nSummary by user:\n";
foreach ($totals as $id => $data) {
    echo "User $id: Total " . round($data['total']/3600, 2) . " hours\n";
    foreach ($data['days'] as $date => $sec) {
        echo "  - $date: " . round($sec/60, 1) . " min\n";
    }
}
