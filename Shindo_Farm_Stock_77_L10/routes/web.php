<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KandangController;
use App\Http\Controllers\TelurController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

// Login (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Logout (harus login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::redirect('/', '/dashboard');

// Semua route data harus login
Route::middleware('auth')->group(function () {
    Route::resource('kandang', KandangController::class);
    Route::resource('telur', TelurController::class);
    Route::resource('penjualan', PenjualanController::class);
    Route::resource('pengeluaran', PengeluaranController::class);
    Route::resource('user', UserController::class);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export');
    Route::get('/test-error/{code}', function ($code) {
    abort((int) $code);
});
});