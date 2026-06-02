<?php
$payload = [
    'token' => 'futika_2026_q7N4vP2xLm8Kc5Rz',
    'action' => 'update_reattestation',
    'discord_id' => '1129175113967882331',
    'result' => 'сдал'
];
$url = 'https://script.google.com/macros/s/AKfycbze9iiP-KgbKtwLoBm9CG20Od_hKSeOTP5zAkXvlnrS3rQ3Bp68q4fpmRxDxYveLlnV8A/exec';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
$err = curl_error($ch);
echo "RESPONSE: " . $res . "\n";
echo "ERROR: " . $err . "\n";
?>
