<?php
$webhook = 'https://script.google.com/macros/s/AKfycbwJ14FaYGLVOEzx-SCVg5EwuhcdGi9pNBA4x8kmFvl1hQJRUVNHBnnTITAojS4FrCmWeA/exec';
$webhookToken = 'futika_2026_q7N4vP2xLm8Kc5Rz';

$payload = [
    'token' => $webhookToken,
    'action' => 'add_support',
    'nick' => 'модель_тест_актив',
    'discord_id' => '1129175113967882331',
    'shift' => '1',
    'date' => date('d.m.Y'),
    'gid' => '2053240546',
    'sheet_gid' => '2053240546',
    'main_sheet_gid' => '2053240546'
];

$webhookUrl = $webhook . '?token=' . $webhookToken . '&action=add_support';

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "RESPONSE: " . $response . "\n";
?>
