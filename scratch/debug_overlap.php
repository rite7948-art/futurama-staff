<?php
require_once 'db.php';
$stmt = $pdo->prepare("SELECT * FROM voice_activity WHERE discord_id = '477534528538148865' ORDER BY end_time DESC LIMIT 20");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Start: {$row['start_time']} | End: {$row['end_time']} | Dur: {$row['duration']}s\n";
}
