<?php
echo "Current time: " . date('Y-m-d H:i:s') . "\n";
echo "Monday this week: " . date('Y-m-d H:i:s', strtotime('monday this week')) . "\n";
echo "Sunday this week: " . date('Y-m-d H:i:s', strtotime('sunday this week')) . "\n";
echo "Last Monday: " . date('Y-m-d H:i:s', strtotime('last monday')) . "\n";
