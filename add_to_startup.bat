@echo off
set "SCRIPT_PATH=%~dp0run_tracker_hidden.vbs"
set "STARTUP_DIR=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"

echo Creating shortcut in Startup folder...
powershell "$s=(New-Object -ComObject WScript.Shell).CreateShortcut('%STARTUP_DIR%\FuturamaVoiceTracker.lnk');$s.TargetPath='%SCRIPT_PATH%';$s.WorkingDirectory='%~dp0';$s.Save()"

echo.
echo ✅ Tracker added to Windows Startup!
echo It will now start automatically when you log in.
echo.
echo Launching now in background...
start "" "%SCRIPT_PATH%"
pause
