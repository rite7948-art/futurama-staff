<?php
function getGoogleSheetCsvUrl($gid) {
    $sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
}
$csvUrl = getGoogleSheetCsvUrl('1970062457');
$csvData = file_get_contents($csvUrl);
$lines = explode("\n", $csvData);
echo "TOTAL ROWS IN OLD SHIFT SHEET: " . count($lines) . "\n";
foreach($lines as $i => $line) {
    if (stripos($line, 'модель_тест') !== false) {
        echo "Line $i: $line\n";
    }
}
?>
