@echo off
echo Compiling Ukikura Warnet Client (Full C++ Win32 + GDI+ + WinHTTP, Static Standalone)...
g++ -std=c++17 -O2 -static src/main.cpp -o WarnetClient.exe -lgdiplus -lwinhttp -lgdi32 -luser32 -lshell32 -mwindows
if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Berhasil dikompilasi menjadi WarnetClient.exe!
) else (
    echo [ERROR] Gagal kompilasi.
)
