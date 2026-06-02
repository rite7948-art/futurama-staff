<?php
require_once 'api.php';
$csvUrl = getGoogleSheetCsvUrl(configValue('MAIN_SHEET_GID', 'main_sheet_gid', '1970062457'));
$csvData = file_get_contents($csvUrl);
$lines = explode("\n", $csvData);
$header = str_getcsv($lines[0]);
echo "Columns found: " . count($header) . "\n";
print_r($header);
foreach(array_slice($lines, 1, 10) as $line) {
    print_r(str_getcsv($line));
}
?>
