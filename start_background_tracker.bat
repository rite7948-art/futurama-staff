@echo off
title Futurama Staff Tracker
:loop
echo [%date% %time%] Checking for staff changes...
php cron_sync.php
echo Waiting 1 hour for next check...
timeout /t 3600 /nobreak
goto loop
