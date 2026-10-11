<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderBlockchainController;

Route::prefix('blockchain')->group(function () {
    Route::post('/record-transaction', [OrderBlockchainController::class, 'recordTransaction']);
    Route::get('/verify-order/{orderCode}', [OrderBlockchainController::class, 'verifyOrder']);
});

