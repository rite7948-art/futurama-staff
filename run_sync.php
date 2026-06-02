<?php
session_start();
header('Content-Type: application/json');

// Увеличиваем время выполнения до 10 минут
set_time_limit(600);

// Проверка прав (админ, гл. куратор, куратор)
$allowed_roles = ['admin', 'chief', 'curator'];
if (!isset($_SESSION['user_logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit;
}

// Команда для запуска скрипта (авто-определение ОС)
if (PHP_OS_FAMILY === 'Windows') {
    $command = 'cmd /c "node check_sync.js"';
} else {
    $command = 'node check_sync.js 2>&1'; // 2>&1 перенаправляет ошибки в основной поток для отладки
}

$output = [];
$return_var = 0;

exec($command, $output, $return_var);

// Логируем в системный журнал Railway для отладки
error_log("Sync command: " . $command);
error_log("Return var: " . $return_var);
error_log("Output: " . implode("\n", $output));

    if ($return_var === 0) {
        // Парсим вывод консоли
        $raw_output = implode("\n", $output);
        
        $results = [
            'success' => true,
            'raw' => $raw_output,
            'sheet_count' => 0,
            'discord_count' => 0,
            'extra' => [],
            'missing' => [],
            'duplicates' => []
        ];

        // Извлекаем цифры
        if (preg_match('/В таблице:.*?(\d+)/u', $raw_output, $matches)) $results['sheet_count'] = (int)$matches[1];
        if (preg_match('/В Discord:.*?(\d+)/u', $raw_output, $matches)) $results['discord_count'] = (int)$matches[1];

        // Извлекаем списки (ищем блоки после заголовков)
        // Лишние (Discord)
        if (strpos($raw_output, '🔴') !== false) {
            $parts = explode('🔴', $raw_output);
            if (isset($parts[1])) {
                $extra_block = explode("\n\n", $parts[1])[0];
                preg_match_all('/ > (.*)/', $extra_block, $matches);
                $results['extra'] = $matches[1] ?? [];
            }
        }

        // Отсутствуют (Таблица)
        if (strpos($raw_output, '🟡') !== false) {
            $parts = explode('🟡', $raw_output);
            if (isset($parts[1])) {
                $missing_block = explode("\n\n", $parts[1])[0];
                preg_match_all('/ > (.*)/', $missing_block, $matches);
                $results['missing'] = $matches[1] ?? [];
            }
        }

        // Дубликаты (Таблица)
        if (strpos($raw_output, '🟠') !== false) {
            $parts = explode('🟠', $raw_output);
            if (isset($parts[1])) {
                $duplicates_block = explode("\n\n", $parts[1])[0];
                preg_match_all('/ > (.*)/', $duplicates_block, $matches);
                $results['duplicates'] = $matches[1] ?? [];
            }
        }

        // --- АВТО-ТРЕКИНГ ИЗМЕНЕНИЙ (СНЯТЫ/ДОБАВЛЕНЫ) ---
        try {
            require_once 'db.php';
            
            // 1. Извлекаем список текущих ID из вывода селфбота
            if (preg_match('/---CURRENT_DISCORD_IDS---\n(.*?)\n---END_CURRENT_DISCORD_IDS---/s', $raw_output, $matches)) {
                $current_ids = array_filter(explode(',', trim($matches[1])));
                
                // 2. Получаем список ID, которые были в прошлый раз
                $stmt = $pdo->query("SELECT discord_id FROM supports_current");
                $last_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // 3. Вычисляем разницу
                $added_ids = array_diff($current_ids, $last_ids);   // Есть сейчас, не было раньше
                $removed_ids = array_diff($last_ids, $current_ids); // Были раньше, нет сейчас
                
                $added_count = count($added_ids);
                $removed_count = count($removed_ids);
                
                // 4. Если есть изменения, записываем их в статистику (по дням)
                $is_first_run = (count($last_ids) === 0);
                
                $stmt = $pdo->prepare("INSERT INTO sync_stats (added_count, removed_count, sheet_total, discord_total) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $is_first_run ? 0 : $added_count, 
                    $is_first_run ? 0 : $removed_count,
                    $results['sheet_count'],
                    $results['discord_count']
                ]);

                // Обновляем список ID
                $pdo->exec("DELETE FROM supports_current");
                $ins = $pdo->prepare("INSERT INTO supports_current (discord_id) VALUES (?)");
                foreach ($current_ids as $cid) {
                    $ins->execute([$cid]);
                }

                // --- ИСТОРИЯ СТАФА: фиксация стажа и уходов ---
                if (!$is_first_run) {
                    require_once 'staff_functions.php';

                    // Карта id => [ник, дата захода] из основного листа состава
                    $idToNick = [];
                    $idToJoin = [];
                    try {
                        $mainGid = configValue('MAIN_SHEET_GID', 'main_sheet_gid', '2053240546');
                        $mainRows = loadCsvRows(getGoogleSheetCsvUrl($mainGid), 60);
                        foreach ($mainRows as $r) {
                            $rid = preg_replace('/[^0-9]/', '', (string) ($r[3] ?? ''));
                            $rnick = trim((string) ($r[2] ?? ''));
                            $rdate = trim((string) ($r[1] ?? ''));
                            if ($rid === '' || $rnick === '' || mb_stripos($rnick, 'смена') !== false) continue;
                            $idToNick[$rid] = $rnick;
                            // дата "встал" в формате дд.мм.гггг -> Y-m-d
                            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $rdate, $m)) {
                                $idToJoin[$rid] = "{$m[3]}-{$m[2]}-{$m[1]}";
                            }
                        }
                    } catch (Exception $e) {}

                    // Запоминаем «первое появление» текущих участников (один раз) + обновляем ник/дату захода
                    $seen = $pdo->prepare(
                        "INSERT INTO staff_seen (discord_id, username, join_date) VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE username = COALESCE(VALUES(username), username), join_date = COALESCE(VALUES(join_date), join_date)"
                    );
                    foreach ($current_ids as $cid) {
                        $seen->execute([$cid, $idToNick[$cid] ?? null, $idToJoin[$cid] ?? null]);
                    }

                    // Логируем ушедших
                    foreach ($removed_ids as $rid) {
                        // данные из staff_seen
                        $s = $pdo->prepare("SELECT username, join_date, first_seen FROM staff_seen WHERE discord_id = ?");
                        $s->execute([$rid]);
                        $sd = $s->fetch(PDO::FETCH_ASSOC) ?: [];

                        // роль и ник из users (если есть аккаунт)
                        $u = $pdo->prepare("SELECT username, role, appointment_date, created_at FROM users WHERE discord_id = ?");
                        $u->execute([$rid]);
                        $ud = $u->fetch(PDO::FETCH_ASSOC) ?: [];

                        $username = $ud['username'] ?? ($sd['username'] ?? null);
                        $role = $ud['role'] ?? 'master';

                        // дата захода: приоритет — таблица/назначение, иначе первое появление при сверке
                        $joined = $sd['join_date'] ?? ($ud['appointment_date'] ?? null);
                        if (!$joined) $joined = isset($sd['first_seen']) ? date('Y-m-d', strtotime($sd['first_seen'])) : ($ud['created_at'] ?? date('Y-m-d'));

                        $days = max(0, (int) floor((time() - strtotime($joined)) / 86400));

                        $pdo->prepare(
                            "INSERT INTO staff_history (discord_id, username, role, joined_at, left_at, days_on_branch)
                             VALUES (?, ?, ?, ?, CURDATE(), ?)"
                        )->execute([$rid, $username, $role, $joined, $days]);

                        $pdo->prepare("DELETE FROM staff_seen WHERE discord_id = ?")->execute([$rid]);
                    }
                }
            }
        } catch (Exception $e) {
            file_put_contents('debug_sync_error.txt', $e->getMessage(), FILE_APPEND);
        }

        echo json_encode($results);
        exit;
    } else {
        $debug_info = implode("\n", $output);
        echo json_encode(['success' => false, 'error' => 'Ошибка при запуске скрипта сверки', 'debug' => $debug_info]);
        exit;
    }
