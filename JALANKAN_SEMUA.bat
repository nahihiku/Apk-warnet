@echo off
title Ukikura iCafe Launcher - Server Laravel Kasir + Dapur + Client C++ (Port 8088)
echo ===============================================================
echo   UKIKURA iCAFE PRO - KASIR LARAVEL + ANTRIAN DAPUR + CLIENT C++
echo ===============================================================
echo.
echo [1/3] Menjalankan Server Laravel (Kasir ^& Antrian Dapur) di port 8088...
start "Server Laravel Kasir & Dapur (8088)" cmd /k "cd /d "%~dp0kasir-laravel" && php artisan serve --host=127.0.0.1 --port=8088"

timeout /t 2 /nobreak >nul

echo [2/3] Membuka Dashboard Kasir ^& Layar Antrian Dapur di Browser...
start http://127.0.0.1:8088
start http://127.0.0.1:8088/dapur

echo [3/3] Menjalankan Aplikasi Client C++ Native (WarnetClient.exe)...
start "" "%~dp0client-cpp\WarnetClient.exe"

echo.
echo Semua sistem telah berjalan di http://127.0.0.1:8088 !
pause
