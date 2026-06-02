<?php
require_once 'db.php';
$stmt = $pdo->query("DESCRIBE voice_activity");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
