<?php
$csvUrl = "https://docs.google.com/spreadsheets/d/e/2PACX-1vR6zZ0Xn_u5n_GvUf7l7R7p7j6R7p7j6R7p7j6R7p7j6R7p7j6R7p7j6R7p7j6R7p7j6R7p7j6/pub?gid=1970062457&single=true&output=csv";
$csv = file_get_contents($csvUrl);
$lines = explode("\n", $csv);
foreach ($lines as $i => $line) {
    $row = str_getcsv($line);
    foreach ($row as $j => $cell) {
        if (mb_strpos($cell, 'Кол-во саппортов') !== false) {
            echo "FOUND 'Кол-во саппортов' at Row $i, Col $j\n";
            $next = str_getcsv($lines[$i+1]);
            echo "Value under it: " . ($next[$j] ?? 'N/A') . "\n";
        }
        if (mb_strpos($cell, 'Итог') !== false) {
            echo "FOUND 'Итог' at Row $i, Col $j\n";
            $next = str_getcsv($lines[$i+1]);
            echo "Value under it: " . ($next[$j] ?? 'N/A') . "\n";
        }
    }
}
?>
