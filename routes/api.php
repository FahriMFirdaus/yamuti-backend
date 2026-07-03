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
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\BroadcastController;
use App\Http\Controllers\Api\DashboardController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:public-forms');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:public-forms');
    // Dummy login route to catch unauthenticated API requests without Accept: application/json
    Route::get('/login', function () {
        return response()->json(['success' => false, 'message' => 'Unauthenticated or Token Invalid.'], 401);
    })->name('login');
    // TODO: forgot-password and reset-password
});

Route::get('/setup-db', function (Request $request) {
    $token = env('SETUP_DB_TOKEN');
    if (!app()->environment('local') && (!$token || $request->query('token') !== $token)) {
        abort(403, 'Unauthorized. Please configure SETUP_DB_TOKEN in Render env and pass it as ?token=... to run migrations.');
    }
    
    try {
        if ($request->query('fresh') === '1') {
            \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        }
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return "Database migration and seeding completed successfully!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', function (Request $request) {
            return $request->user()->load('roles.permissions');
        });
    });

    // ==========================================
    // AREA SUPER ADMIN (Manajemen Akses & Staf)
    // ==========================================
    Route::middleware('role:super_admin')->group(function () {
        // Role & Permission Management
        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::get('/roles', [RoleController::class, 'index']);
        Route::post('/roles', [RoleController::class, 'store']);
        Route::get('/roles/{id}', [RoleController::class, 'show']);
        Route::put('/roles/{id}', [RoleController::class, 'update']);
        Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

        // Admin Management
        Route::apiResource('admins', AdminController::class);
    });

    // ==========================================
    // AREA OPERASIONAL (Admin & Super Admin)
    // ==========================================
    Route::middleware('role:super_admin|admin')->group(function () {
        // Dashboard
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

        // Core Business Logic
        Route::apiResource('anak-asuh', AnakAsuhController::class);
        Route::apiResource('inventaris', InventarisController::class);
        Route::get('/inventaris/{inventarisId}/mutasi', [App\Http\Controllers\Api\MutasiBarangController::class, 'index']);
        Route::post('/inventaris/{inventarisId}/mutasi', [App\Http\Controllers\Api\MutasiBarangController::class, 'store']);
        
        // Keuangan & Laporan
        Route::apiResource('transaksi-keuangan', App\Http\Controllers\Api\TransaksiKeuanganController::class);
        Route::get('/keuangan/laporan', [TransaksiKeuanganController::class, 'laporan']);
        
        // Epic 3.1: Artikel & Galeri (Hanya Hak Tulis/Ubah untuk Admin)
        Route::apiResource('kategori-artikel', KategoriArtikelController::class)->except(['index', 'show']);
        Route::apiResource('artikel', App\Http\Controllers\Api\ArtikelController::class)->except(['index', 'show']);
        Route::apiResource('galeri', App\Http\Controllers\Api\GaleriController::class)->except(['index', 'show']);

        // Kampanye Crowdfunding (Protected endpoints for Admin)
        Route::apiResource('kampanye', App\Http\Controllers\Api\KampanyeController::class)->except(['index', 'show']);

        // Broadcast
        Route::post('/broadcast/send', [BroadcastController::class, 'send']);

        // Donasi & Kas
        Route::get('/donasi', [DonasiController::class, 'index']);
        Route::get('/donasi/{id}', [DonasiController::class, 'show']);
        Route::patch('/donasi/{id}/verify', [DonasiController::class, 'verify']);
        
        // Data Donatur (CRM)
        Route::get('/donatur', [\App\Http\Controllers\Api\DonaturController::class, 'index']);
        Route::get('/donatur/{id}', [\App\Http\Controllers\Api\DonaturController::class, 'show']);
        
        Route::get('/transaksi', [TransaksiKeuanganController::class, 'index']);
        Route::post('/transaksi', [TransaksiKeuanganController::class, 'store']);
        Route::get('/kas/saldo', [TransaksiKeuanganController::class, 'saldo']);
        
        // Kunjungan (Terlindungi)
        Route::get('/kunjungan', [KunjunganController::class, 'index']);
        Route::get('/kunjungan/{id}', [KunjunganController::class, 'show']);
        Route::patch('/kunjungan/{id}/status', [KunjunganController::class, 'updateStatus']);
    });
});

// Endpoint publik untuk Kampanye
Route::get('/kampanye', [\App\Http\Controllers\Api\KampanyeController::class, 'index']);
Route::get('/kampanye/{id}', [\App\Http\Controllers\Api\KampanyeController::class, 'show']);

// Endpoint publik untuk Donatur membuat donasi (di luar auth)
Route::post('/donasi', [DonasiController::class, 'store'])->middleware('throttle:public-forms');
Route::post('/midtrans/webhook', [\App\Http\Controllers\Api\MidtransWebhookController::class, 'handle']);

// Endpoint publik untuk Artikel & Galeri (Transparansi)
Route::get('/artikel', [\App\Http\Controllers\Api\ArtikelController::class, 'index']);
Route::get('/artikel/{id}', [\App\Http\Controllers\Api\ArtikelController::class, 'show']);
Route::get('/kategori-artikel', [\App\Http\Controllers\Api\KategoriArtikelController::class, 'index']);
Route::get('/kategori-artikel/{id}', [\App\Http\Controllers\Api\KategoriArtikelController::class, 'show']);
Route::get('/galeri', [\App\Http\Controllers\Api\GaleriController::class, 'index']);
Route::get('/galeri/{id}', [\App\Http\Controllers\Api\GaleriController::class, 'show']);

// Endpoint publik untuk pendaftaran tamu Kunjungan
Route::post('/kunjungan', [KunjunganController::class, 'store'])->middleware('throttle:public-forms');

// Endpoint publik untuk mendapatkan kontak admin utama
Route::get('/kontak-utama', [\App\Http\Controllers\Api\ProfileController::class, 'kontakUtama']);

// Rute Profil & Riwayat (Bisa diakses oleh semua User yang sedang login)
Route::middleware('auth:sanctum')->group(function () {
    // Profil Mandiri
    Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::put('/profile/password', [\App\Http\Controllers\Api\ProfileController::class, 'updatePassword']);

    // Riwayat
    Route::get('/user/riwayat-donasi', [DonasiController::class, 'riwayat']);
    Route::get('/user/riwayat-kunjungan', [KunjunganController::class, 'riwayat']);
});
