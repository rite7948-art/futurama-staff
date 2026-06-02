<?php
$webhook = 'https://script.google.com/macros/s/AKfycbwC3qOp-KsF-LDKWcb5TlRq3hqaW74tW0FPWSbfyZ-oghCuvslvSXNfBx7aVSaMRgLF1w/exec';
$token = 'futika_2026_q7N4vP2xLm8Kc5Rz';

$payload = [
    'token' => $token,
    'action' => 'add_support',
    'nick' => 'тест_нов_скрипт',
    'discord_id' => '1129175113967882331',
    'shift' => '3',
    'date' => date('d.m.Y')
];

$url = $webhook . '?token=' . $token . '&action=add_support';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

echo "HTTP: $httpCode\n";
echo "RESPONSE: $response\n";
echo "ERROR: $err\n";
?>
