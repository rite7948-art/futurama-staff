<?php
function getGoogleSheetCsvUrl($gid) {
    $sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
}
$csvUrl = getGoogleSheetCsvUrl('1970062457');
$csvData = file_get_contents($csvUrl);
$lines = explode("\n", $csvData);
$header = str_getcsv($lines[0]);
echo "Columns found: " . count($header) . "\n";
print_r($header);
foreach(array_slice($lines, 1, 10) as $line) {
    print_r(str_getcsv($line));
}
?>
