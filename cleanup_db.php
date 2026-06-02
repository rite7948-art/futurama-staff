<?php
require 'db.php';
$pdo->exec("DELETE FROM sync_stats WHERE discord_total = 0");
echo "База очищена от битых записей (с нулями).";
?>
