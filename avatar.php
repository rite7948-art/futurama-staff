<?php
/**
 * Прокси аватарок: дёргает Discord API напрямую (через токен бота из env DISCORD_TOKEN)
 * и редиректит на CDN-картинку. Кэшируется на час чтобы не спамить Discord.
 * Если токена нет / юзер не найден — отдаёт Dicebear-плейсхолдер.
 */

$discord_id = $_GET['id'] ?? '';
$username = $_GET['seed'] ?? 'default';

function fallback($username) {
    header("Location: https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($username) . "&backgroundColor=b6e3f4,c0aede,d1d4f9");
    exit;
}

if (!$discord_id || $discord_id === 'system' || !is_numeric($discord_id)) {
    fallback($username);
}

// Кэш на диске — 1 час
$cacheDir = __DIR__ . '/cache/avatars';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0777, true);
$cacheFile = $cacheDir . '/' . $discord_id;
if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 3600)) {
    $url = @file_get_contents($cacheFile);
    if ($url) { header("Location: $url"); exit; }
}

// Токен бота — нужен чтобы запросить юзера у Discord
$token = getenv('DISCORD_TOKEN');
if (!$token) {
    fallback($username);
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://discord.com/api/v10/users/" . urlencode($discord_id));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bot $token"]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$resp = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http === 200 && $resp) {
    $data = json_decode($resp, true);
    if (!empty($data['avatar'])) {
        $ext = (strpos($data['avatar'], 'a_') === 0) ? 'gif' : 'png';
        $avatarUrl = "https://cdn.discordapp.com/avatars/{$discord_id}/{$data['avatar']}.{$ext}?size=128";
        @file_put_contents($cacheFile, $avatarUrl);
        header("Location: $avatarUrl");
        exit;
    }
}

// Дефолтный аватар Discord (для аккаунтов без аватарки)
$idx = (intdiv((int)$discord_id, 1 << 22)) % 6;
@file_put_contents($cacheFile, "https://cdn.discordapp.com/embed/avatars/$idx.png");
header("Location: https://cdn.discordapp.com/embed/avatars/$idx.png");
exit;
