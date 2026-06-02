@echo off
set PHP_PATH=C:\xampp\php\php.exe
set BROWSER_PATH="C:\Program Files\Yandex\YandexBrowser\Application\browser.exe"
set PORT=8000

echo Launching PHP Local Server on http://127.0.0.1:%PORT%...
:: Kill any existing process on this port first
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :%PORT%') do taskkill /F /PID %%a 2>nul

start /B %PHP_PATH% -S 127.0.0.1:%PORT%

echo Opening Yandex Browser...
timeout /t 2 >nul
start "" %BROWSER_PATH% http://127.0.0.1:%PORT%

echo.
echo Server is running at http://127.0.0.1:%PORT%
echo You can close this window, the server will keep running in the background.
pause
