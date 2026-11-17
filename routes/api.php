<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnakAsuhController;
use App\Http\Controllers\Api\AnakAsuhRecordController;
use App\Http\Controllers\Api\ArtikelController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BroadcastController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DonasiController;
use App\Http\Controllers\Api\DonaturController;
use App\Http\Controllers\Api\GaleriController;
use App\Http\Controllers\Api\InventarisController;
use App\Http\Controllers\Api\KampanyeController;
use App\Http\Controllers\Api\KampanyeUpdateController;
use App\Http\Controllers\Api\KategoriArtikelController;
use App\Http\Controllers\Api\KeranjangController;
use App\Http\Controllers\Api\KunjunganController;
use App\Http\Controllers\Api\LandingController;
use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Api\MutasiBarangController;
use App\Http\Controllers\Api\OrangTuaAsuhController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SocialiteController;
use App\Http\Controllers\Api\TransaksiKeuanganController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:public-forms');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    // Dummy login route to catch unauthenticated API requests without Accept: application/json
    Route::get('/login', function () {
        return response()->json(['success' => false, 'message' => 'Unauthenticated or Token Invalid.'], 401);
    })->name('login');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:reset-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:reset-password');

    // Google OAuth
    Route::get('/google/redirect', [SocialiteController::class, 'redirect']);
    Route::get('/google/callback', [SocialiteController::class, 'callback']);
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

        // System Settings (Super Admin Only)
        Route::post('/settings/split-rule', [SettingController::class, 'updateSplitRule']);
        Route::post('/settings/lokasi-operasional', [SettingController::class, 'updateLokasiOperasional']);
        Route::post('/settings/nisab-zakat', [SettingController::class, 'updateNisabZakat']);
    });

    // ==========================================
    // AREA OPERASIONAL (Admin & Super Admin)
    // ==========================================
    Route::middleware('role:super_admin|admin')->group(function () {
        // Dashboard
        Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

        // Core Business Logic
        Route::apiResource('anak-asuh', AnakAsuhController::class);
        Route::get('/anak-asuh/{anakAsuhId}/records', [AnakAsuhRecordController::class, 'index']);
        Route::post('/anak-asuh/{anakAsuhId}/records', [AnakAsuhRecordController::class, 'store']);
        Route::delete('/anak-asuh/records/{id}', [AnakAsuhRecordController::class, 'destroy']);
        Route::apiResource('orang-tua-asuh', OrangTuaAsuhController::class);
        Route::apiResource('inventaris', InventarisController::class);
        Route::get('/inventaris/{inventarisId}/mutasi', [MutasiBarangController::class, 'index']);
        Route::post('/inventaris/{inventarisId}/mutasi', [MutasiBarangController::class, 'store']);

        // Keuangan & Laporan
        Route::apiResource('transaksi-keuangan', TransaksiKeuanganController::class);
        Route::get('/keuangan/laporan', [TransaksiKeuanganController::class, 'laporan']);

        // Epic 3.1: Artikel & Galeri (Hanya Hak Tulis/Ubah untuk Admin)
        Route::post('/artikel/upload-image', [ArtikelController::class, 'uploadImage']);
        Route::apiResource('kategori-artikel', KategoriArtikelController::class)->except(['index', 'show']);
        Route::apiResource('artikel', ArtikelController::class)->except(['index', 'show']);
        Route::apiResource('galeri', GaleriController::class)->except(['index', 'show']);

        // Kampanye Crowdfunding (Protected endpoints for Admin)
        Route::apiResource('kampanye', KampanyeController::class)->except(['index', 'show']);
        Route::post('/kampanye/{kampanyeId}/updates', [KampanyeUpdateController::class, 'store']);
        Route::delete('/kampanye/updates/{id}', [KampanyeUpdateController::class, 'destroy']);

        // Broadcast
        Route::post('/broadcast/send', [BroadcastController::class, 'send'])->middleware('throttle:broadcast');

        // Donasi & Kas
        Route::get('/donasi', [DonasiController::class, 'index']);
        Route::get('/donasi/{id}', [DonasiController::class, 'show']);
        Route::patch('/donasi/{id}/verify', [DonasiController::class, 'verify']);
        Route::post('/donasi/manual', [DonasiController::class, 'storeManual'])->middleware('throttle:public-forms');

        // Data Donatur (CRM)
        Route::get('/donatur', [DonaturController::class, 'index']);
        Route::get('/donatur/{id}', [DonaturController::class, 'show']);

        Route::get('/transaksi', [TransaksiKeuanganController::class, 'index']);
        Route::post('/transaksi', [TransaksiKeuanganController::class, 'store']);
        Route::get('/kas/saldo', [TransaksiKeuanganController::class, 'saldo']);

        // Kunjungan (Terlindungi)
        Route::get('/kunjungan', [KunjunganController::class, 'index']);
        Route::get('/kunjungan/{id}', [KunjunganController::class, 'show']);
        Route::patch('/kunjungan/{id}/status', [KunjunganController::class, 'updateStatus']);

        // System Settings (Read-only for Admin & Super Admin)
        Route::get('/settings', [SettingController::class, 'index']);
        Route::get('/settings/lokasi-operasional', [SettingController::class, 'getLokasiOperasional']);
        Route::get('/settings/nisab-zakat', [SettingController::class, 'getNisabZakat']);
    });
});

// Endpoint publik untuk Kampanye
Route::get('/kampanye', [KampanyeController::class, 'index']);
Route::get('/kampanye/{id}', [KampanyeController::class, 'show']);
Route::get('/kampanye/{id}/updates', [KampanyeUpdateController::class, 'index']);

// Endpoint publik untuk Donatur membuat donasi (di luar auth)
Route::post('/donasi', [DonasiController::class, 'store'])->middleware('throttle:public-forms');
Route::post('/midtrans/webhook', [MidtransWebhookController::class, 'handle']);

// Endpoint publik untuk Artikel & Galeri (Transparansi)
Route::get('/artikel', [ArtikelController::class, 'index']);
Route::get('/artikel/{id}', [ArtikelController::class, 'show']);
Route::get('/kategori-artikel', [KategoriArtikelController::class, 'index']);
Route::get('/kategori-artikel/{id}', [KategoriArtikelController::class, 'show']);
Route::get('/galeri', [GaleriController::class, 'index']);
Route::get('/galeri/{id}', [GaleriController::class, 'show']);

// Endpoint publik untuk Kebutuhan Logistik Mendesak (Inventaris Stok Menipis)
Route::get('/kebutuhan-mendesak', [InventarisController::class, 'mendesak']);

// Endpoint publik untuk Kunjungan
Route::get('/kunjungan/ketersediaan', [KunjunganController::class, 'ketersediaan']);
Route::post('/kunjungan', [KunjunganController::class, 'store'])->middleware('throttle:public-forms');

// Endpoint publik untuk mendapatkan kontak admin utama
Route::get('/kontak-utama', [ProfileController::class, 'kontakUtama']);

// Endpoint publik untuk Landing Page Frontend
Route::prefix('landing')->group(function () {
    Route::get('/impact', [LandingController::class, 'impact']);
    Route::get('/benefits', [LandingController::class, 'benefits']);
    Route::get('/slides', [LandingController::class, 'slides']);
    Route::get('/doa', [LandingController::class, 'doa']);
    Route::get('/operasional', [SettingController::class, 'getLokasiOperasional']);
    Route::get('/nisab-zakat', [SettingController::class, 'getNisabZakat']);
});

// Rute Profil & Riwayat (Bisa diakses oleh semua User yang sedang login)
Route::middleware('auth:sanctum')->group(function () {
    // Profil Mandiri
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // Riwayat & Program Donatur
    Route::get('/user/riwayat-donasi', [DonasiController::class, 'riwayat']);
    Route::get('/user/riwayat-kunjungan', [KunjunganController::class, 'riwayat']);
    Route::get('/user/orang-tua-asuh', [OrangTuaAsuhController::class, 'saya']);
    Route::get('/user/anak-asuh-tersedia', [OrangTuaAsuhController::class, 'availableAnakAsuh']);
    Route::post('/user/orang-tua-asuh', [OrangTuaAsuhController::class, 'daftarMandiri']);

    // Keranjang Donasi
    Route::get('/keranjang/count', [KeranjangController::class, 'count']);
    Route::get('/keranjang', [KeranjangController::class, 'index']);
    Route::post('/keranjang', [KeranjangController::class, 'store']);
    Route::delete('/keranjang/{id}', [KeranjangController::class, 'destroy']);
    Route::post('/keranjang/checkout', [KeranjangController::class, 'checkout']);
});
