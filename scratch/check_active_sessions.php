<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT * FROM active_voice_sessions");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($sessions as $s) {
    $dur = time() - strtotime($s['start_time']);
    echo "User {$s['discord_id']} | Started: {$s['start_time']} | Active for: " . round($dur/3600, 2) . " hours\n";
}
