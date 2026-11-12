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

// =========================================================
// REST API ROUTES: UNTUK CLIENT C++ NATIVE (NO CSRF)
// =========================================================
Route::prefix('api/client')
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->group(function () {
        Route::get('/catalog', [ClientApiController::class, 'catalog']);
        Route::post('/heartbeat', [ClientApiController::class, 'heartbeat']);
        Route::post('/login', [ClientApiController::class, 'login']);
        Route::post('/logout', [ClientApiController::class, 'logout']);
        Route::post('/buy-billing', [ClientApiController::class, 'buyBilling']);
        Route::post('/order-food', [ClientApiController::class, 'orderFood']);
        Route::post('/confirm-qris', [ClientApiController::class, 'confirmQris']);
    });
