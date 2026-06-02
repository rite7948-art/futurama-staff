<?php
session_start();
header('Content-Type: application/json');

// Проверка прав
$allowed_roles = ['admin', 'chief', 'curator'];
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit;
}

set_time_limit(60);

if (PHP_OS_FAMILY === 'Windows') {
    $command = 'cmd /c "node check_channels.js"';
} else {
    $command = 'node check_channels.js 2>&1';
}

$output = [];
$return_var = 0;
exec($command, $output, $return_var);

$raw = implode("\n", $output);

if (preg_match('/---CHANNELS_DATA---\n(.*?)\n---END_CHANNELS_DATA---/s', $raw, $matches)) {
    $channels = json_decode(trim($matches[1]), true);
    if ($channels === null) {
        echo json_encode(['success' => false, 'error' => 'Не удалось разобрать JSON', 'raw' => $raw]);
        exit;
    }
    echo json_encode(['success' => true, 'channels' => $channels]);
} else {
    echo json_encode(['success' => false, 'error' => 'Нет данных от селф-бота', 'raw' => $raw]);
}
