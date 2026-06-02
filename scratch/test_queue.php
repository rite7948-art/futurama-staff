<?php
require_once 'db.php';
require_once 'api.php';

echo "Testing getReattestationQueue():\n";
$queue = getReattestationQueue();
echo "Count: " . count($queue) . "\n";
print_r($queue);
?>
