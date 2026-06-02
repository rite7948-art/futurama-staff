<?php
$files = glob("*.php");
echo "PHP FILES FOUND:\n";
foreach ($files as $file) {
    echo "- $file\n";
}
?>
