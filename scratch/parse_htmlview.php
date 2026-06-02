<?php
$sheetId = '1w2r_C3R7kh5CDvlehOHOjd3DPnvCMBQ9SnXZnB6t754';
$url = "https://docs.google.com/spreadsheets/d/{$sheetId}/htmlview";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$html = curl_exec($ch);
curl_close($ch);

echo "HTML length: " . strlen($html) . "\n";

// Search for tab names and GIDs
// In htmlview, tabs are represented as list items or links with gid:
// <a href="#gid=..." ...>Tab Name</a>
preg_match_all('/#gid=([0-9]+)[^>]*>(.*?)<\/a>/i', $html, $matches);

echo "TABS FOUND:\n";
if (!empty($matches[0])) {
    for ($i = 0; $i < count($matches[1]); $i++) {
        $gid = $matches[1][$i];
        $name = trim(strip_tags($matches[2][$i]));
        echo "Tab: '$name' (GID: $gid)\n";
    }
} else {
    echo "No tabs parsed using standard regex.\n";
    // Print a snippet of the HTML to see what's there
    echo "HTML snippet:\n" . substr($html, 0, 1000) . "\n";
}
?>
