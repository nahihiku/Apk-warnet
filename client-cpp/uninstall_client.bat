@echo off
setlocal enabledelayedexpansion
title UNINSTALLER & REMOVE AUTORUN CLIENT WARNET UKIKURA ICAFE

echo =====================================================================
echo       UNINSTALLER UKIKURA ICAFE CLIENT C++
echo =====================================================================
echo.

:: Periksa Hak Akses Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Meminta elevasi hak akses Administrator...
    powershell -Command "Start-Process cmd -ArgumentList '/c \"\"%~f0\"\"' -Verb RunAs"
    exit /b
)

echo 1. Menghentikan proses WarnetClient.exe jika masih berjalan...
taskkill /F /IM WarnetClient.exe >nul 2>&1

echo 2. Menghapus pendaftaran Windows Startup Autorun...
reg delete "HKCU\Software\Microsoft\Windows\CurrentVersion\Run" /v "UkikuraWarnetClient" /f >nul 2>&1

echo 3. Menghapus aturan Windows Firewall...
netsh advfirewall firewall delete rule name="Ukikura Warnet Client" >nul 2>&1

echo 4. Menghapus Shortcut Desktop...
powershell -Command "$p = [System.IO.Path]::Combine([Environment]::GetFolderPath('Desktop'), 'Ukikura Warnet Client.lnk'); if (Test-Path $p) { Remove-Item $p -Force }" >nul 2>&1

echo 5. Memulihkan Taskbar Windows...
powershell -Command "$code = @'
using System;
using System.Runtime.InteropServices;
public class Win32 {
    [DllImport(\"user32.dll\")]
    public static extern IntPtr FindWindow(string lpClassName, string lpWindowName);
    [DllImport(\"user32.dll\")]
    public static extern bool ShowWindow(IntPtr hWnd, int nCmdShow);
    [DllImport(\"user32.dll\")]
    public static extern bool EnableWindow(IntPtr hWnd, bool bEnable);
}
'@; Add-Type $code; $tb = [Win32]::FindWindow('Shell_TrayWnd', $null); if ($tb -ne [IntPtr]::Zero) { [Win32]::ShowWindow($tb, 5); [Win32]::EnableWindow($tb, $true); }" >nul 2>&1

echo.
echo =====================================================================
echo UNINSTALL SELESAI
echo Startup autorun, aturan firewall, dan shortcut telah dibersihkan.
echo =====================================================================
echo.
pause
