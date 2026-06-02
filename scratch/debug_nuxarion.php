<?php
require_once 'db.php';
$id = '1337712604872704092';
$monday = date('Y-m-d 00:00:00', strtotime('monday this week'));

echo "Monday start: $monday\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";

$stmt = $pdo->prepare("SELECT DATE(start_time) as d, SUM(duration) as s FROM voice_activity WHERE discord_id = ? AND start_time >= ? GROUP BY d");
$stmt->execute([$id, $monday]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->prepare("SELECT * FROM voice_activity WHERE discord_id = ? ORDER BY id DESC LIMIT 10");
$stmt->execute([$id]);
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
