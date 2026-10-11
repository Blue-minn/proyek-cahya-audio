<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlockchainDashboardController;

Route::get('/blockchain/dashboard', [BlockchainDashboardController::class, 'index'])->name('blockchain.dashboard');

