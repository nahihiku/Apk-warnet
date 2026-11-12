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
