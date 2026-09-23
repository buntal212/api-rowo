<?php

use App\Http\Controllers\Api\MiuranController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')
    ->prefix('v1/setting')
    ->group(function () {

        Route::get(
            '/getlistiuran',
            [MiuranController::class, 'index']
        );

        Route::post(
            '/simpaniuran',
            [MiuranController::class, 'store']
        );

    });
