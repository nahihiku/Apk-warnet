@echo off
setlocal enabledelayedexpansion
title INSTALLER ^& AUTO SETUP CLIENT WARNET UKIKURA ICAFE

echo =====================================================================
echo       INSTALLER ^& AUTO-SETUP UKIKURA ICAFE CLIENT C++
echo =====================================================================
echo Skrip ini akan melakukan instalasi dan konfigurasi otomatis:
echo  1. Konfigurasi Nomor Terminal PC dan Alamat IP Server Kasir
echo  2. Pendaftaran Startup Otomatis Windows (Auto-start on Boot)
echo  3. Pengaturan Pengecualian Windows Firewall (LAN Communication)
echo  4. Pembuatan Shortcut Desktop
echo  5. Penguncian Kiosk Layar Penuh (Menutup Taskbar ^& Tombol Windows)
echo =====================================================================
echo.

:: 1. Periksa Hak Akses Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Meminta elevasi hak akses Administrator...
    powershell -Command "Start-Process cmd -ArgumentList '/c \"\"%~f0\"\"' -Verb RunAs"
    exit /b
)

set CLIENT_DIR=%~dp0
set CLIENT_DIR=%CLIENT_DIR:~0,-1%
set CLIENT_EXE=%CLIENT_DIR%\WarnetClient.exe
set CONFIG_FILE=%CLIENT_DIR%\client_config.ini

if not exist "%CLIENT_EXE%" (
    if exist "%CLIENT_DIR%\build.bat" (
        echo [INFO] File WarnetClient.exe belum ditemukan. Menjalankan kompilasi otomatis...
        call "%CLIENT_DIR%\build.bat"
    )
    if not exist "%CLIENT_EXE%" (
        echo [ERROR] File WarnetClient.exe tidak ditemukan di %CLIENT_DIR%.
        echo         Unduh dari Server Kasir ^(/download/client^) atau salin WarnetClient.exe ke folder ini.
        pause
        exit /b 1
    )
)

echo [OK] File eksekutif client terverifikasi: %CLIENT_EXE%
echo.

:: 2. Tanya Konfigurasi Stasiun PC & Server Kasir (baca nilai client_config.ini jika sudah ada)
set DEFAULT_PC=PC-01
set DEFAULT_HOST=127.0.0.1
set DEFAULT_PORT=8088

if exist "%CONFIG_FILE%" (
    for /f "usebackq tokens=1,2 delims==" %%A in ("%CONFIG_FILE%") do (
        if /i "%%A"=="pc_code" if not "%%B"=="" set DEFAULT_PC=%%B
        if /i "%%A"=="server_host" if not "%%B"=="" set DEFAULT_HOST=%%B
        if /i "%%A"=="server_port" if not "%%B"=="" set DEFAULT_PORT=%%B
    )
)

echo ---------------------------------------------------------------------
echo PENGATURAN IDENTITAS TERMINAL PC ^& SERVER KASIR
echo ---------------------------------------------------------------------
if /i "%~1"=="/silent" (
    set USER_PC=%DEFAULT_PC%
    set USER_HOST=%DEFAULT_HOST%
    set USER_PORT=%DEFAULT_PORT%
) else (
    set /p USER_PC="Masukkan Kode Terminal PC (contoh: PC-01, PC-08) [Default %DEFAULT_PC%]: "
    if "!USER_PC!"=="" set USER_PC=%DEFAULT_PC%

    set /p USER_HOST="Masukkan Alamat IP Server Kasir (LAN/VM) [Default %DEFAULT_HOST%]: "
    if "!USER_HOST!"=="" set USER_HOST=%DEFAULT_HOST%

    set /p USER_PORT="Masukkan Port Server Kasir [Default %DEFAULT_PORT%]: "
    if "!USER_PORT!"=="" set USER_PORT=%DEFAULT_PORT%
)

:: Simpan ke client_config.ini
(
    echo [Client]
    echo pc_code=%USER_PC%
    echo server_host=%USER_HOST%
    echo server_port=%USER_PORT%
) > "%CONFIG_FILE%"

echo [OK] File konfigurasi berhasil diperbarui: %CONFIG_FILE%
echo      - Kode PC   : %USER_PC%
echo      - IP Server : %USER_HOST%:%USER_PORT%
echo.

:: 3. Daftarkan ke Windows Startup Autorun (Registry Run)
echo ---------------------------------------------------------------------
echo MENDAFTARKAN AUTORUN WINDOWS STARTUP
echo ---------------------------------------------------------------------
reg add "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v "UkikuraWarnetClient" /t REG_SZ /d "\"%CLIENT_EXE%\"" /f >nul
if %errorLevel% equ 0 (
    echo [OK] Client berhasil didaftarkan ke Windows Startup Autorun.
    echo      Aplikasi akan langsung berjalan otomatis setiap kali komputer dinyalakan.
) else (
    echo [WARNING] Gagal menambahkan autorun registry.
)
echo.

:: 4. Tambahkan Pengecualian Windows Firewall
echo ---------------------------------------------------------------------
echo MENGATUR PENGECUALIAN WINDOWS FIREWALL
echo ---------------------------------------------------------------------
netsh advfirewall firewall delete rule name="Ukikura Warnet Client" >nul 2>&1
netsh advfirewall firewall add rule name="Ukikura Warnet Client" dir=in action=allow program="%CLIENT_EXE%" enable=yes profile=any >nul 2>&1
netsh advfirewall firewall add rule name="Ukikura Warnet Client" dir=out action=allow program="%CLIENT_EXE%" enable=yes profile=any >nul 2>&1
echo [OK] Rule Windows Firewall berhasil diaktifkan untuk koneksi LAN Kasir.
echo.

:: 5. Buat Shortcut di Desktop
echo ---------------------------------------------------------------------
echo MEMBUAT SHORTCUT DESKTOP
echo ---------------------------------------------------------------------
powershell -Command "$ws = New-Object -ComObject WScript.Shell; $s = $ws.CreateShortcut([System.IO.Path]::Combine([Environment]::GetFolderPath('Desktop'), 'Ukikura Warnet Client.lnk')); $s.TargetPath = '%CLIENT_EXE%'; $s.WorkingDirectory = '%CLIENT_DIR%'; $s.Save()" >nul 2>&1
echo [OK] Shortcut Desktop berhasil dibuat.
echo.

:: 6. Konfirmasi Selesai & Peluncuran
echo =====================================================================
echo INSTALASI ^& SETUP CLIENT SELESAI DENGAN SUKSES!
echo.
echo Catatan Keamanan Client Warnet:
echo  - Mode Terkunci: Layar penuh borderless (Topmost), taskbar disembunyikan.
echo  - Tombol Windows, Alt+Tab, Alt+Esc, Ctrl+Esc, dan Alt+F4 diblokir.
echo  - Setelah pelanggan login, taskbar muncul kembali dan game dapat dimainkan.
echo  - Tersedia katalog game dan Bilah Mini melayang agar tidak menutupi game.
echo  - Khusus Teknisi: Tekan F2 untuk membuka menu konfigurasi PC kapan saja.
echo =====================================================================
echo.
set /p LAUNCH_NOW="Apakah ingin menjalankan Ukikura Warnet Client sekarang? (Y/N) [Default Y]: "
if "%LAUNCH_NOW%"=="" set LAUNCH_NOW=Y
if /i "%LAUNCH_NOW%"=="Y" (
    echo [INFO] Menjalankan WarnetClient.exe...
    start "" "%CLIENT_EXE%"
)

echo.
echo Selesai.
pause
