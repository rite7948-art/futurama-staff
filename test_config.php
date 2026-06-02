<?php
require_once 'staff_functions.php';
$config = getAppConfig();
echo "<h1>Config Debug</h1>";
echo "Current Dir: " . __DIR__ . "<br>";
echo "Config Keys: " . implode(', ', array_keys($config)) . "<br>";
echo "Webhook URL: " . ($config['app_script_webhook_url'] ?? 'MISSING') . "<br>";
echo "PHP Version: " . phpversion() . "<br>";
