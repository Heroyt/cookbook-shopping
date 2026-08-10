<?php

declare(strict_types=1);

use App\Cookbook\Http\Controllers\IngredientController;
use App\Cookbook\Http\Controllers\StoreController;
use App\Cookbook\Http\Controllers\StoreSectionAssociationController;
use App\Cookbook\Http\Controllers\StoreSectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('ingredients', [IngredientController::class, 'index'])->name('ingredients.index');
    Route::post('ingredients', [IngredientController::class, 'store'])->name('ingredients.store');
    Route::patch('ingredients/{ingredient}', [IngredientController::class, 'update'])->name('ingredients.update');
    Route::get('stores', [StoreController::class, 'index'])->name('stores.index');
    Route::post('stores', [StoreController::class, 'store'])->name('stores.store');
    Route::patch('stores/{store}', [StoreController::class, 'update'])->name('stores.update');
    Route::delete('stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
    Route::post('stores/{store}/store-sections', [StoreSectionAssociationController::class, 'store'])
        ->name('stores.store-sections.store');
    Route::delete('stores/{store}/store-sections/{storeSection}', [StoreSectionAssociationController::class, 'destroy'])
        ->name('stores.store-sections.destroy');
    Route::put('stores/{store}/store-sections', [StoreSectionAssociationController::class, 'update'])
        ->name('stores.store-sections.update');
    Route::post('store-sections', [StoreSectionController::class, 'store'])->name('store-sections.store');
    Route::delete('store-sections/{storeSection}', [StoreSectionController::class, 'destroy'])
        ->name('store-sections.destroy');
});
