<?php

use App\Http\Controllers\Api\PengeluaranMasjidController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/pengeluaran-masjid')
    ->group(function () {
        Route::get('/getlist', [PengeluaranMasjidController::class, 'getList']);
        Route::post('/simpan', [PengeluaranMasjidController::class, 'simpan']);
        Route::post('/header/{pengeluaranMasjid}', [PengeluaranMasjidController::class, 'ubahHeader']);
        Route::post('/hapus-header/{pengeluaranMasjid}', [PengeluaranMasjidController::class, 'hapusHeader']);
        Route::post('/hapus-rincian/{rincian}', [PengeluaranMasjidController::class, 'hapusRincian']);
    });
