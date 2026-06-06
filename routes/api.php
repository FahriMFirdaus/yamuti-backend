<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\AnakAsuhController;
use App\Http\Controllers\Api\InventarisController;
use App\Http\Controllers\Api\DonasiController;
use App\Http\Controllers\Api\TransaksiKeuanganController;
use App\Http\Controllers\Api\KunjunganController;
use App\Http\Controllers\Api\KategoriArtikelController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/setup-db', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return "Database migration and seeding completed successfully!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Role & Permission Management
    Route::get('/permissions', [RoleController::class, 'permissions']);
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    // Core Business Logic
    Route::apiResource('anak-asuh', AnakAsuhController::class);
    Route::apiResource('inventaris', InventarisController::class);
    Route::apiResource('mutasi-barang', App\Http\Controllers\Api\MutasiBarangController::class);
    Route::apiResource('transaksi-keuangan', App\Http\Controllers\Api\TransaksiKeuanganController::class);
    
    // Epic 3.1: Artikel & Galeri
    Route::apiResource('kategori-artikel', KategoriArtikelController::class);
    Route::apiResource('artikel', App\Http\Controllers\Api\ArtikelController::class);
    Route::apiResource('galeri', App\Http\Controllers\Api\GaleriController::class)->except(['update']);

    // Donasi & Kas
    Route::get('/donasi', [DonasiController::class, 'index']);
    Route::get('/donasi/{id}', [DonasiController::class, 'show']);
    Route::post('/donasi/{id}/mark-paid', [DonasiController::class, 'markAsPaid']);
    
    Route::get('/transaksi', [TransaksiKeuanganController::class, 'index']);
    Route::post('/transaksi', [TransaksiKeuanganController::class, 'store']);
    Route::get('/kas/saldo', [TransaksiKeuanganController::class, 'saldo']);
    
    // Kunjungan (Terlindungi)
    Route::get('/kunjungan', [KunjunganController::class, 'index']);
    Route::get('/kunjungan/{id}', [KunjunganController::class, 'show']);
    Route::post('/kunjungan/{id}/approve', [KunjunganController::class, 'approve']);
    Route::post('/kunjungan/{id}/reject', [KunjunganController::class, 'reject']);
});

// Endpoint publik untuk Donatur membuat donasi (di luar auth)
Route::post('/donasi', [DonasiController::class, 'store']);
Route::post('/midtrans/webhook', [\App\Http\Controllers\Api\MidtransWebhookController::class, 'handle']);

// Endpoint publik untuk pendaftaran tamu Kunjungan
Route::post('/kunjungan', [KunjunganController::class, 'store']);

