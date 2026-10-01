<?php

use App\Http\Controllers\Api\LaporanIuranWargaController;
use App\Http\Controllers\Api\LaporanKasUmumController;
use App\Http\Controllers\Api\LaporanPengeluaranController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/laporan')
    ->group(function () {
        Route::get('/iuran-warga', [LaporanIuranWargaController::class, 'index']);
        Route::get('/iuran-warga/detail', [LaporanIuranWargaController::class, 'detail']);
        Route::get('/pengeluaran', [LaporanPengeluaranController::class, 'index']);
        Route::get('/kas-umum', [LaporanKasUmumController::class, 'index']);
    });
