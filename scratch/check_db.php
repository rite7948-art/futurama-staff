<?php
require 'db.php';
$stmt = $pdo->query('SELECT * FROM reattestations ORDER BY id DESC LIMIT 1');
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "LATEST RECORD:\n";
    print_r($row);
} else {
    echo "NO RECORDS FOUND.\n";
}
?>
