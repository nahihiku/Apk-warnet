<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\ClientApiController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

// =========================================================
// WEB ROUTES: KASIR DASHBOARD & LAYAR ANTRIAN DAPUR (KDS)
// =========================================================
Route::get('/', [KasirController::class, 'index'])->name('kasir.dashboard');
Route::get('/dapur', [KasirController::class, 'dapur'])->name('kasir.dapur');
Route::get('/kasir/live-state', [KasirController::class, 'liveState'])->name('kasir.live');

Route::post('/kasir/pc-control', [KasirController::class, 'controlPc'])->name('kasir.pc.control');

// PC Terminal Management (Add, Edit, Delete, Batch Sizer)
Route::post('/kasir/pcs/store', [KasirController::class, 'storePc'])->name('kasir.pc.store');
Route::post('/kasir/pcs/{pc}/update', [KasirController::class, 'updatePc'])->name('kasir.pc.update');
Route::post('/kasir/pcs/{pc}/delete', [KasirController::class, 'destroyPc'])->name('kasir.pc.delete');
Route::post('/kasir/pcs/batch-generate', [KasirController::class, 'batchGeneratePcs'])->name('kasir.pc.batch');

// Member Routes (Top-Up Saldo/Paket, Create, Edit/Update, Delete)
Route::post('/kasir/member-topup', [KasirController::class, 'topupMember'])->name('kasir.member.topup');
Route::post('/kasir/member-store', [KasirController::class, 'storeMember'])->name('kasir.member.store');
Route::post('/kasir/members/{member}/update', [KasirController::class, 'updateMember'])->name('kasir.member.update');
Route::post('/kasir/members/{member}/delete', [KasirController::class, 'destroyMember'])->name('kasir.member.delete');

// Menu & Package Routes (Create, Edit/Update, Delete)
Route::post('/kasir/menu-store', [KasirController::class, 'storeMenu'])->name('kasir.menu.store');
Route::post('/kasir/menus/{menu}/update', [KasirController::class, 'updateMenu'])->name('kasir.menu.update');
Route::post('/kasir/menus/{menu}/delete', [KasirController::class, 'destroyMenu'])->name('kasir.menu.delete');

Route::post('/kasir/package-store', [KasirController::class, 'storePackage'])->name('kasir.package.store');
Route::post('/kasir/packages/{package}/update', [KasirController::class, 'updatePackage'])->name('kasir.package.update');
Route::post('/kasir/packages/{package}/delete', [KasirController::class, 'destroyPackage'])->name('kasir.package.delete');

// Order & Kitchen Status Routes
Route::post('/kasir/orders/{order}/kitchen-status', [KasirController::class, 'updateKitchenStatus'])->name('kasir.order.kitchen');
Route::post('/kasir/orders/{order}/confirm-payment', [KasirController::class, 'confirmOrderPayment'])->name('kasir.order.pay');

// Diskless Server & Game Vault Management
Route::post('/kasir/diskless/toggle-superuser', [KasirController::class, 'toggleSuperUser'])->name('kasir.diskless.superuser');
Route::post('/kasir/diskless/config', [KasirController::class, 'updateDisklessConfig'])->name('kasir.diskless.config');
Route::post('/kasir/diskless/scan-games', [KasirController::class, 'scanServerGames'])->name('kasir.diskless.scan');
Route::post('/kasir/server-games/store', [KasirController::class, 'storeCustomServerGame'])->name('kasir.games.store');
Route::post('/kasir/server-games/{game}/update', [KasirController::class, 'updateServerGame'])->name('kasir.games.update');
Route::post('/kasir/server-games/{game}/delete', [KasirController::class, 'destroyServerGame'])->name('kasir.games.delete');
Route::post('/kasir/vault/store', [KasirController::class, 'storeVaultAccount'])->name('kasir.vault.store');
Route::post('/kasir/vault/{account}/release', [KasirController::class, 'releaseVaultAccount'])->name('kasir.vault.release');
Route::post('/kasir/vault/{account}/delete', [KasirController::class, 'destroyVaultAccount'])->name('kasir.vault.delete');

// =========================================================
// REST API ROUTES: UNTUK CLIENT C++ NATIVE (NO CSRF)
// =========================================================
Route::prefix('api/client')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function () {
        Route::match(['get', 'post'], '/catalog', [ClientApiController::class, 'catalog']);
        Route::post('/heartbeat', [ClientApiController::class, 'heartbeat']);
        Route::post('/login', [ClientApiController::class, 'login']);
        Route::post('/logout', [ClientApiController::class, 'logout']);
        Route::post('/buy-billing', [ClientApiController::class, 'buyBilling']);
        Route::post('/order-food', [ClientApiController::class, 'orderFood']);
        Route::post('/confirm-qris', [ClientApiController::class, 'confirmQris']);
        Route::post('/game-vault/borrow', [ClientApiController::class, 'borrowGameVault']);
        Route::post('/game-vault/return', [ClientApiController::class, 'returnGameVault']);
    });

// VM & Client Deployment Endpoints (HTTP direct download for VirtualBox / LAN PCs)
Route::get('/download/client', function () {
    $path = realpath(base_path('../client-cpp/WarnetClient.exe'));
    abort_unless($path && file_exists($path), 404, 'WarnetClient.exe belum dikompilasi di server.');
    return response()->download($path, 'WarnetClient.exe');
})->name('download.client');

Route::get('/download/installer', function () {
    $path = realpath(base_path('../client-cpp/install_client.bat'));
    abort_unless($path && file_exists($path), 404, 'install_client.bat tidak ditemukan.');
    return response()->download($path, 'install_client.bat');
})->name('download.installer');

Route::get('/download/uninstaller', function () {
    $path = realpath(base_path('../client-cpp/uninstall_client.bat'));
    abort_unless($path && file_exists($path), 404, 'uninstall_client.bat tidak ditemukan.');
    return response()->download($path, 'uninstall_client.bat');
})->name('download.uninstaller');

Route::get('/download/setup.ps1', function (\Illuminate\Http\Request $request) {
    $baseUrl = $request->getSchemeAndHttpHost();
    $serverHost = $request->getHost();
    $serverPort = (int) ($request->getPort() ?: 8088);
    $pcCode = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $request->query('pc', 'PC-01')) ?: 'PC-01';

    $template = <<<'PS1'
$ErrorActionPreference = 'Stop'
$baseUrl = '__BASE_URL__'
$serverHost = '__SERVER_HOST__'
$serverPort = '__SERVER_PORT__'
$pcCode = '__PC_CODE__'
$installDir = 'C:\UkikuraClient'

Write-Host '=== UKIKURA iCAFE VM / CLIENT SETUP ===' -ForegroundColor Cyan
Stop-Process -Name WarnetClient -Force -ErrorAction SilentlyContinue
New-Item -ItemType Directory -Force -Path $installDir | Out-Null

Write-Host "[1/4] Mengunduh WarnetClient.exe dari $baseUrl ..."
Invoke-WebRequest -Uri "$baseUrl/download/client" -OutFile "$installDir\WarnetClient.exe" -UseBasicParsing

Write-Host '[2/4] Mengunduh install_client.bat & uninstall_client.bat ...'
Invoke-WebRequest -Uri "$baseUrl/download/installer" -OutFile "$installDir\install_client.bat" -UseBasicParsing
Invoke-WebRequest -Uri "$baseUrl/download/uninstaller" -OutFile "$installDir\uninstall_client.bat" -UseBasicParsing

Write-Host "[3/4] Menulis client_config.ini (PC=$pcCode, Server=${serverHost}:${serverPort}, PIN=1234) ..."
@"
[Client]
pc_code=$pcCode
server_host=$serverHost
server_port=$serverPort
operator_pin=1234
"@ | Set-Content -Path "$installDir\client_config.ini" -Encoding ASCII

Write-Host '[4/4] Siap dijalankan! Membuka folder instalasi & menjalankan installer...'
Start-Process cmd.exe -ArgumentList '/c', "`"$installDir\install_client.bat`"" -Verb RunAs
PS1;

    $script = strtr($template, [
        '__BASE_URL__' => $baseUrl,
        '__SERVER_HOST__' => $serverHost,
        '__SERVER_PORT__' => (string) $serverPort,
        '__PC_CODE__' => $pcCode,
    ]);

    return response($script, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
})->name('download.setup');

Route::get('/download/share-server.bat', function (\Illuminate\Http\Request $request) {
    $cfg = \App\Models\DisklessServerConfig::getGameShareConfig($request->getHost());
    $srcPrimary = $cfg['source_path'];
    $namePrimary = $cfg['share_name'];
    $srcExtra = $cfg['extra_source_path'];
    $nameExtra = $cfg['extra_share_name'];
    $shareUser = $cfg['share_user'] !== '' ? $cfg['share_user'] : 'UkikuraClient';
    $sharePass = $cfg['share_pass'];

    $template = <<<'BAT'
@echo off
title UKIKURA ICAFE - BAGIKAN DRIVE GAME SERVER (ADMIN)
color 0B

:: Minta hak Administrator otomatis via UAC
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] Meminta izin Administrator untuk membuat Network Share Game Server...
    powershell -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

echo ============================================================================
echo    UKIKURA ICAFE - KONFIGURASI SERVER NETWORK GAME DISK
echo ============================================================================
echo.
echo   Folder Sumber Utama : __SRC_PRIMARY__
echo   Nama Share Utama    : __NAME_PRIMARY__
echo   Folder Tambahan     : __SRC_EXTRA__
echo   Nama Share Tambahan : __NAME_EXTRA__
echo.

echo [1/4] Konfigurasi akun Read-Only Windows SMB (untuk Windows 11 Client)
set "SMB_USER=__SHARE_USER__"
set "SMB_PASS=__SHARE_PASS__"
set /p "INPUT_USER=Nama akun SMB Read-Only [Default: %SMB_USER%]: "
if not "%INPUT_USER%"=="" set "SMB_USER=%INPUT_USER%"
if "%SMB_PASS%"=="" (
    echo   (Penting: Windows 11 Client memblokir guest tanpa password secara default.)
    set /p "SMB_PASS=Masukkan password untuk akun %SMB_USER% (contoh: ukikura123): "
)

set "GRANT_CLAUSE=/GRANT:Everyone,READ"
if not "%SMB_PASS%"=="" (
    net user "%SMB_USER%" >nul 2>&1
    if errorlevel 1 (
        net user "%SMB_USER%" "%SMB_PASS%" /add /comment:"Ukikura Game Share Read-Only" /passwordchg:no
    ) else (
        net user "%SMB_USER%" "%SMB_PASS%" /passwordchg:no
    )
    powershell -NoProfile -Command "Try { Set-LocalUser -Name '%SMB_USER%' -PasswordNeverExpires $true } Catch {}" >nul 2>&1
    set "GRANT_CLAUSE=/GRANT:%SMB_USER%,READ /GRANT:Everyone,READ"
)

echo.
echo [2/4] Membuat SMB Share Utama: __NAME_PRIMARY__ -^> __SRC_PRIMARY__
set "PS_SRC=__SRC_PRIMARY__"
set "PS_NAME=__NAME_PRIMARY__"
powershell -NoProfile -Command "$n=$env:PS_NAME; $p=$env:PS_SRC; $u=$env:SMB_USER; $acc=@('Everyone'); if($u -and $env:SMB_PASS){ $acc+= $u }; Remove-SmbShare -Name $n -Force -ErrorAction SilentlyContinue; New-SmbShare -Name $n -Path $p -ReadAccess $acc -Description 'Ukikura Master Game Disk' | Out-Null"
if errorlevel 1 (
    echo [ERROR] Gagal membagikan __SRC_PRIMARY__. Pastikan folder/drive tersedia!
) else (
    echo [OK] Share __NAME_PRIMARY__ aktif! Client dapat mengakses \\SERVER\__NAME_PRIMARY__
)

echo.
echo [3/4] Membuat SMB Share Tambahan (jika folder ada): __NAME_EXTRA__ -^> __SRC_EXTRA__
set "PS_SRC2=__SRC_EXTRA__"
set "PS_NAME2=__NAME_EXTRA__"
if exist "__SRC_EXTRA__" (
    powershell -NoProfile -Command "$n=$env:PS_NAME2; $p=$env:PS_SRC2; $u=$env:SMB_USER; $acc=@('Everyone'); if($u -and $env:SMB_PASS){ $acc+= $u }; Remove-SmbShare -Name $n -Force -ErrorAction SilentlyContinue; New-SmbShare -Name $n -Path $p -ReadAccess $acc -Description 'Ukikura Secondary Games' | Out-Null"
    if not errorlevel 1 echo [OK] Share __NAME_EXTRA__ aktif!
) else (
    echo [SKIP] Folder __SRC_EXTRA__ tidak ditemukan, dilewati.
)

echo.
echo [4/4] Membuka Firewall File ^& Printer Sharing (SMB Port 445)
netsh advfirewall firewall set rule group="File and Printer Sharing" new enable=Yes >nul 2>&1
netsh advfirewall firewall add rule name="Ukikura Server Game Share SMB 445" dir=in action=allow protocol=TCP localport=445 >nul 2>&1

echo.
echo ============================================================================
echo   SELESAI! LANGKAH TERAKHIR DI DASHBOARD KASIR (Tab Diskless ^& Vault):
if not "%SMB_PASS%"=="" (
    echo   Masukkan SMB User: %SMB_USER%   ^|   SMB Pass: %SMB_PASS%
    echo   lalu klik "Simpan Pengaturan Game Disk Server".
) else (
    echo   Share dibuka dalam mode Everyone Read-Only.
)
echo   Setelah itu tekan F2/Sinkron di WarnetClient.exe Client/VM!
echo ============================================================================
echo.
pause
BAT;

    $content = strtr($template, [
        '__SRC_PRIMARY__' => $srcPrimary,
        '__NAME_PRIMARY__' => $namePrimary,
        '__SRC_EXTRA__' => $srcExtra,
        '__NAME_EXTRA__' => $nameExtra,
        '__SHARE_USER__' => $shareUser,
        '__SHARE_PASS__' => $sharePass,
    ]);
    $content = str_replace("\r\n", "\n", $content);
    $content = str_replace("\n", "\r\n", $content);

    return response($content, 200, [
        'Content-Type' => 'application/octet-stream',
        'Content-Disposition' => 'attachment; filename="BAGIKAN_DISK_GAME_SERVER.bat"',
    ]);
})->name('download.share_server');

Route::get('/vm-setup', function (\Illuminate\Http\Request $request) {
    $baseUrl = htmlspecialchars($request->getSchemeAndHttpHost(), ENT_QUOTES, 'UTF-8');
    $host = htmlspecialchars($request->getHost(), ENT_QUOTES, 'UTF-8');
    $port = (int) ($request->getPort() ?: 8088);
    $html = <<<HTML
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ukikura iCafe - Download & Setup Client VM</title>
<style>
  body { margin:0; font-family:Segoe UI,system-ui,sans-serif; background:#0b1120; color:#e2e8f0; padding:32px; }
  .wrap { max-width:760px; margin:0 auto; background:#111a2e; border:1px solid #1e293b; border-radius:12px; padding:28px; }
  h1 { margin:0 0 8px; font-size:22px; color:#38bdf8; }
  p { color:#94a3b8; line-height:1.5; }
  .box { background:#090f1d; border:1px solid #24324a; border-radius:8px; padding:14px 16px; margin:14px 0; font-family:Consolas,monospace; font-size:13px; color:#a5f3fc; overflow-x:auto; }
  .btns { display:flex; flex-wrap:wrap; gap:10px; margin:18px 0; }
  a.btn { display:inline-block; padding:10px 16px; border-radius:8px; text-decoration:none; font-weight:600; font-size:14px; background:#0284c7; color:#fff; }
  a.btn.sec { background:#1e293b; color:#e2e8f0; border:1px solid #334155; }
  ul { color:#cbd5e1; line-height:1.7; padding-left:20px; }
  code { background:#1e293b; padding:2px 6px; border-radius:4px; color:#7dd3fc; }
</style>
</head>
<body>
<div class="wrap">
  <h1>Ukikura iCafe - Setup Client di VM / PC Client</h1>
  <p>Server Kasir terdeteksi di: <code>{$host}:{$port}</code></p>

  <h3>Cara Cepat (1 Baris di PowerShell VM)</h3>
  <div class="box">irm {$baseUrl}/download/setup.ps1 | iex</div>
  <p>Perintah di atas otomatis mengunduh <code>WarnetClient.exe</code>, membuat <code>client_config.ini</code> mengarah ke <code>{$host}:{$port}</code>, lalu membuka installer administrator.</p>

  <h3>Download Manual</h3>
  <div class="btns">
    <a class="btn" href="/download/client">Download WarnetClient.exe</a>
    <a class="btn sec" href="/download/installer">Download install_client.bat</a>
    <a class="btn sec" href="/download/uninstaller">Download uninstall_client.bat</a>
    <a class="btn sec" href="/download/share-server.bat">Share Drive Server (Jalankan di Server)</a>
  </div>

  <h3>Panduan Uji Real di VM</h3>
  <ul>
    <li>Saat <code>install_client.bat</code> dijalankan: tekan Enter untuk mempertahankan konfigurasi <code>{$host}:{$port}</code> dan <code>PC-01</code> (atau ganti misalnya <code>PC-08</code> untuk tes tier VIP).</li>
    <li>Mode Kiosk akan menutup taskbar dan mengunci tombol Windows hingga login dilakukan.</li>
    <li>Akun demo siap pakai: <code>sultan01</code> atau <code>gamer02</code> (atau login mode Paket Kasir/Guest dari Dashboard Kasir).</li>
    <li>Tekan <code>F2</code> dan masukkan PIN Operator (default <code>1234</code>) untuk mengganti Kode PC, IP Server, atau keluar aplikasi.</li>
    <li>Tekan <code>F11</code> untuk toggle Fullscreen/Windowed setelah login (dikunci penuh sebelum login).</li>
    <li>Untuk mencabut autorun startup dari VM setelah tes selesai, jalankan <code>uninstall_client.bat</code> (Run as Administrator).</li>
  </ul>
</div>
</body>
</html>
HTML;
    return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
})->name('vm.setup');
