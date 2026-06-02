<?php
require 'db.php';
$stmt = $pdo->query("SELECT * FROM sync_stats ORDER BY id DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
