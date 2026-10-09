<?php

use App\Http\Controllers\Api\PemasukanKotakMasjidController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/pemasukan-kotak-masjid')
    ->group(function () {
        Route::get('/getlist', [PemasukanKotakMasjidController::class, 'getList']);
        Route::post('/simpan', [PemasukanKotakMasjidController::class, 'simpan']);
        Route::post('/hapus/{pemasukanKotakMasjid}', [PemasukanKotakMasjidController::class, 'hapus']);
    });
