<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT * FROM voice_activity ORDER BY id DESC LIMIT 20");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
    echo "Table voice_activity is EMPTY.\n";
} else {
    print_r($rows);
}

$stmt = $pdo->query("SELECT discord_id, SUM(duration) as total FROM voice_activity GROUP BY discord_id");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
