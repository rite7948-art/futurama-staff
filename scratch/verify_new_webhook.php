<?php
function getRows($gid) {
    $sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
    $data = file_get_contents($url);
    return explode("\n", $data);
}

// Проверяем ТЕКУЩУЮ вкладку (2053240546)
$lines = getRows('2053240546');
$found = false;
foreach ($lines as $i => $l) {
    if (stripos($l, 'тест_нов') !== false) {
        echo "✅ НАЙДЕНО в GID 2053240546 (ТЕКУЩАЯ), строка $i: $l\n";
        $found = true;
    }
}
if (!$found) echo "❌ НЕ найдено в GID 2053240546 (ТЕКУЩАЯ)\n";

// Проверяем СТАРУЮ вкладку (1970062457)
$lines2 = getRows('1970062457');
$found2 = false;
foreach ($lines2 as $i => $l) {
    if (stripos($l, 'тест_нов') !== false) {
        echo "⚠️  НАЙДЕНО в GID 1970062457 (СТАРАЯ), строка $i: $l\n";
        $found2 = true;
    }
}
if (!$found2) echo "✅ НЕ найдено в GID 1970062457 (СТАРАЯ) — хорошо!\n";
?>
