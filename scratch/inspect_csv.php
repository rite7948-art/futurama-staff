<?php
require_once 'db.php';
require_once 'staff_functions.php';

$csvUrl = getGoogleSheetCsvUrl(configValue('MAIN_SHEET_GID', 'main_sheet_gid', '1970062457'));
$rows = loadCsvRows($csvUrl);

echo "Total rows: " . count($rows) . "\n";
for ($i = 0; $i < 50; $i++) {
    if (!isset($rows[$i])) break;
    echo "Row $i: " . implode(" | ", array_slice($rows[$i], 0, 10)) . "\n";
}
