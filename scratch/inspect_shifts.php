<?php
function getGoogleSheetCsvUrl($gid)
{
    $sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
    return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
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

$rows = loadCsvRows(getGoogleSheetCsvUrl('2053240546'));

echo "TOTAL ROWS: " . count($rows) . "\n";

foreach ($rows as $i => $row) {
    $rowStr = implode('|', $row);
    if (stripos($rowStr, 'смена') !== false || stripos($rowStr, 'собес') !== false || stripos($rowStr, 'смены') !== false) {
        echo "Row $i: " . $rowStr . "\n";
    }
}
?>
