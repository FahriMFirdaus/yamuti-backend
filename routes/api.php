<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\AnakAsuhController;
use App\Http\Controllers\Api\InventarisController;
use App\Http\Controllers\Api\DonasiController;
use App\Http\Controllers\Api\TransaksiKeuanganController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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

    // Donasi & Kas
    Route::get('/donasi', [DonasiController::class, 'index']);
    Route::get('/donasi/{id}', [DonasiController::class, 'show']);
    Route::post('/donasi/{id}/mark-paid', [DonasiController::class, 'markAsPaid']);
    
    Route::get('/transaksi', [TransaksiKeuanganController::class, 'index']);
    Route::post('/transaksi', [TransaksiKeuanganController::class, 'store']);
    Route::get('/kas/saldo', [TransaksiKeuanganController::class, 'saldo']);
});

// Endpoint publik untuk Donatur membuat donasi (di luar auth)
Route::post('/donasi', [DonasiController::class, 'store']);

