<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PendudukController;

Route::middleware('auth:sanctum')
    ->prefix('v1/penduduk')
    ->group(function () {

        Route::get(
            '/getlist',
            [PendudukController::class, 'getList']
        );

        Route::get(
            '/getdetail',
            [PendudukController::class, 'getDetail']
        );

        Route::post(
            '/simpan',
            [PendudukController::class, 'simpan']
        );

        Route::post(
            '/delete',
            [PendudukController::class, 'delete']
        );

    });
