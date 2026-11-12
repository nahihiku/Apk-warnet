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
New-Item -ItemType Directory -Force -Path $installDir | Out-Null

Write-Host "[1/4] Mengunduh WarnetClient.exe dari $baseUrl ..."
Invoke-WebRequest -Uri "$baseUrl/download/client" -OutFile "$installDir\WarnetClient.exe" -UseBasicParsing

Write-Host '[2/4] Mengunduh install_client.bat & uninstall_client.bat ...'
Invoke-WebRequest -Uri "$baseUrl/download/installer" -OutFile "$installDir\install_client.bat" -UseBasicParsing
Invoke-WebRequest -Uri "$baseUrl/download/uninstaller" -OutFile "$installDir\uninstall_client.bat" -UseBasicParsing

Write-Host "[3/4] Menulis client_config.ini (PC=$pcCode, Server=${serverHost}:${serverPort}) ..."
@"
[Client]
pc_code=$pcCode
server_host=$serverHost
server_port=$serverPort
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
  </div>

  <h3>Panduan Uji Real di VM</h3>
  <ul>
    <li>Saat <code>install_client.bat</code> dijalankan: tekan Enter untuk mempertahankan konfigurasi <code>{$host}:{$port}</code> dan <code>PC-01</code> (atau ganti misalnya <code>PC-08</code> untuk tes tier VIP).</li>
    <li>Mode Kiosk akan menutup taskbar dan mengunci tombol Windows hingga login dilakukan.</li>
    <li>Akun demo siap pakai: <code>sultan01</code> atau <code>gamer02</code> (atau login mode Paket Kasir/Guest dari Dashboard Kasir).</li>
    <li>Tekan <code>F2</code> untuk mengganti Kode PC atau IP Server kapan saja, dan <code>F11</code> untuk toggle Fullscreen/Windowed saat debugging.</li>
    <li>Untuk mencabut autorun startup dari VM setelah tes selesai, jalankan <code>uninstall_client.bat</code> (Run as Administrator).</li>
  </ul>
</div>
</body>
</html>
HTML;
    return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
})->name('vm.setup');
