<?php

declare(strict_types=1);

use App\Cookbook\Http\Controllers\StoreController;
use App\Cookbook\Http\Controllers\StoreSectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
    Route::patch('stores/{store}', [StoreController::class, 'update'])->name('stores.update');
    Route::delete('stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
    Route::post('store-sections', [StoreSectionController::class, 'store'])->name('store-sections.store');
});
