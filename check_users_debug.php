<?php
require_once 'db.php';
$stmt = $pdo->query("SELECT username, password, role FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<h2>Список пользователей в БД:</h2><pre>";
print_r($users);
echo "</pre>";
