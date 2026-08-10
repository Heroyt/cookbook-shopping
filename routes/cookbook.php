<?php

declare(strict_types=1);

use App\Cookbook\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
    Route::patch('stores/{store}', [StoreController::class, 'update'])->name('stores.update');
});
