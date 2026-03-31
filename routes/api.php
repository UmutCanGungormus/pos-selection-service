<?php

use App\Http\Controllers\PosRateController;
use App\Http\Controllers\PosRateSyncController;
use App\Http\Controllers\PosSelectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('pos')->name('pos.')->middleware('throttle:60,1')->group(function () {
    Route::post('/select', PosSelectionController::class)->name('select');

    Route::get('/rates', [PosRateController::class, 'index'])->name('rates');

    Route::post('/sync', PosRateSyncController::class)->name('sync');
});
