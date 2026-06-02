<?php
function getGoogleSheetCsvUrl($gid)
{
    $sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    $url = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv";
    if ($gid !== null && $gid !== '') {
        $url .= "&gid={$gid}";
    }
    return $url;
}

function loadCsvRows($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $csvData = curl_exec($ch);
    curl_close($ch);

    $rows = [];
    $temp = fopen('php://temp', 'r+');
    fwrite($temp, $csvData);
    rewind($temp);
    while (($row = fgetcsv($temp)) !== false) {
        $rows[] = $row;
    }
    fclose($temp);
    return $rows;
}

$rows = loadCsvRows(getGoogleSheetCsvUrl(''));

echo "TOTAL ROWS IN DEFAULT SHEET: " . count($rows) . "\n";
echo "First row: " . implode('|', $rows[0] ?? []) . "\n";
echo "Second row: " . implode('|', $rows[1] ?? []) . "\n";
echo "Third row: " . implode('|', $rows[2] ?? []) . "\n";
echo "Fourth row: " . implode('|', $rows[3] ?? []) . "\n";
?>
