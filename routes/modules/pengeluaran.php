<?php

use App\Http\Controllers\Api\PengeluaranController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/pengeluaran')
    ->group(function () {
        Route::get('/getlist', [PengeluaranController::class, 'getList']);
        Route::post('/simpan', [PengeluaranController::class, 'simpan']);
    });
