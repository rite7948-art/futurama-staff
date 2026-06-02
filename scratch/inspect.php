<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM reports");
    echo "Total reports: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT id, master_name, candidate_id, created_at FROM reports ORDER BY created_at DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Latest 10 reports:\n";
    print_r($rows);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
