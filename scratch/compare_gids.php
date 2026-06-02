<?php
function getGoogleSheetCsvUrl($gid) {
    $sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
}
function printRows($gid, $name) {
    $csvData = file_get_contents(getGoogleSheetCsvUrl($gid));
    $lines = explode("\n", $csvData);
    echo "--- FIRST 15 ROWS OF $name (GID: $gid) ---\n";
    foreach(array_slice($lines, 0, 15) as $i => $line) {
        echo "Row " . ($i + 1) . ": " . implode('|', str_getcsv($line)) . "\n";
    }
    echo "\n";
}

printRows('2053240546', 'GID 2053240546 (Shifts Link GID)');
printRows('1970062457', 'GID 1970062457 (Main Sheet GID Default)');
?>
