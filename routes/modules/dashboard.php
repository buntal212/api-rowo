<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('v1/dashboard')
    ->group(function () {
        Route::get('/saldo-rukem', [DashboardController::class, 'saldoRukem']);
        Route::get('/saldo-kotak-masjid', [DashboardController::class, 'saldoKotakMasjid']);
    });
