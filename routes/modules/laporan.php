<?php

use App\Http\Controllers\Api\LaporanIuranWargaController;
use App\Http\Controllers\Api\LaporanKasUmumController;
use App\Http\Controllers\Api\LaporanKasUmumMasjidController;
use App\Http\Controllers\Api\LaporanPengeluaranController;
use App\Http\Controllers\Api\LaporanPengeluaranMasjidController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/laporan')
    ->group(function () {
        Route::get('/iuran-warga', [LaporanIuranWargaController::class, 'index']);
        Route::get('/iuran-warga/detail', [LaporanIuranWargaController::class, 'detail']);
        Route::get('/pengeluaran', [LaporanPengeluaranController::class, 'index']);
        Route::get('/pengeluaran-masjid', [LaporanPengeluaranMasjidController::class, 'index']);
        Route::get('/kas-umum', [LaporanKasUmumController::class, 'index']);
        Route::get('/kas-umum-masjid', [LaporanKasUmumMasjidController::class, 'index']);
    });
