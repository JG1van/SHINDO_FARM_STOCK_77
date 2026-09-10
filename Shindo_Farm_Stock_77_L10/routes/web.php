<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KandangController;
use App\Http\Controllers\TelurController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PengeluaranController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\KalkulatorController;
use App\Http\Controllers\ProfilController;

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

// Nota print PUBLIK (tanpa login) — untuk aplikasi printer eksternal via URL
Route::get('/penjualan/{id}/print', [PenjualanController::class, 'print'])->name('penjualan.print');

// Semua route data harus login
Route::middleware('auth')->group(function () {
    Route::resource('kandang', KandangController::class);
    Route::resource('telur', TelurController::class);
    Route::resource('penjualan', PenjualanController::class)->except(['show']);
    Route::resource('pengeluaran', PengeluaranController::class);
    Route::resource('user', UserController::class);
    Route::post('/user/{id}/reset-password', [UserController::class, 'resetPassword'])->name('user.reset-password');
    Route::get('/user/{id}/statistics', [UserController::class, 'statistics'])->name('user.statistics');
    Route::get('/activity-history', [UserController::class, 'activityHistory'])->name('activityHistory');
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::put('/profil/password', [ProfilController::class, 'password'])->name('profil.password');
    Route::post('/profil/deactivate', [ProfilController::class, 'deactivate'])->name('profil.deactivate');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/export-excel', [DashboardController::class, 'exportExcel'])->name('dashboard.export');
    Route::get('/kalkulator', [KalkulatorController::class, 'index'])->name('kalkulator.index');
});