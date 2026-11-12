@echo off
echo Compiling Ukikura Warnet Client (Full C++ Win32 + GDI+ + WinHTTP)...
g++ -std=c++17 -O2 src/main.cpp -o WarnetClient.exe -lgdiplus -lwinhttp -lgdi32 -luser32 -mwindows
if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Berhasil dikompilasi menjadi WarnetClient.exe!
) else (
    echo [ERROR] Gagal kompilasi.
)
