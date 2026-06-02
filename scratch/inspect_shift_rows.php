<?php
function getGoogleSheetCsvUrl($gid) {
    $sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
}
$rows = [];
$csvData = file_get_contents(getGoogleSheetCsvUrl('2053240546'));
$temp = fopen('php://temp', 'r+');
fwrite($temp, $csvData);
rewind($temp);
while (($row = fgetcsv($temp)) !== false) {
    $rows[] = $row;
}
fclose($temp);

echo "PRINTING ROWS 9 TO 35:\n";
for ($i = 8; $i < 35; $i++) {
    if (isset($rows[$i])) {
        echo "Row " . ($i + 1) . ": " . implode('|', array_map(function($v) { return $v === '' ? '' : $v; }, $rows[$i])) . "\n";
    }
}
?>
