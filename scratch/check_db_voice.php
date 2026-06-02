<?php
require_once 'db.php';

try {
    echo "Checking voice_activity table:\n";
    $stmt = $pdo->query("SELECT * FROM voice_activity ORDER BY id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);

    echo "\nChecking active_voice_sessions table:\n";
    $stmt = $pdo->query("SELECT * FROM active_voice_sessions");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
