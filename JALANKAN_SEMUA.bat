@echo off
title Ukikura iCafe Launcher - Server Laravel Kasir + Dapur + Client C++ (Port 8088)
echo ===============================================================
echo   UKIKURA iCAFE PRO - KASIR LARAVEL + ANTRIAN DAPUR + CLIENT C++
echo ===============================================================
echo.
echo [1/3] Menjalankan Server Laravel (Kasir ^& Antrian Dapur) di 0.0.0.0:8088 (LAN ^& VM Ready)...
start "Server Laravel Kasir & Dapur (8088)" cmd /k "cd /d "%~dp0kasir-laravel" && php artisan serve --host=0.0.0.0 --port=8088"

timeout /t 2 /nobreak >nul

echo [2/3] Membuka Dashboard Kasir ^& Layar Antrian Dapur di Browser...
start http://127.0.0.1:8088
start http://127.0.0.1:8088/dapur

echo [3/3] Menjalankan Aplikasi Client C++ Native (WarnetClient.exe)...
start "" "%~dp0client-cpp\WarnetClient.exe"

echo.
echo Dashboard Kasir Lokal : http://127.0.0.1:8088
echo Setup VM (NAT)        : http://10.0.2.2:8088/vm-setup
pause
