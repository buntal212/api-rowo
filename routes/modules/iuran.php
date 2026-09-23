<?php

use App\Http\Controllers\Api\IuranController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/iuran')
    ->group(function () {
        Route::get('/getlist', [IuranController::class, 'getList']);
        Route::post('/simpan', [IuranController::class, 'simpan']);
    });
