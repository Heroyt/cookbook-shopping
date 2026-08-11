<?php

declare(strict_types=1);

use App\Cookbook\Http\Controllers\IngredientAlternativeController;
use App\Cookbook\Http\Controllers\IngredientController;
use App\Cookbook\Http\Controllers\RecipeController;
use App\Cookbook\Http\Controllers\RecipeTagController;
use App\Cookbook\Http\Controllers\StoreController;
use App\Cookbook\Http\Controllers\StoreSectionAssociationController;
use App\Cookbook\Http\Controllers\StoreSectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('recipes', [RecipeController::class, 'index'])->name('recipes.index');
    Route::post('recipes', [RecipeController::class, 'store'])->name('recipes.store');
    Route::put('recipes/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
    Route::patch('recipes/{recipe}/archive', [RecipeController::class, 'archive'])->name('recipes.archive');
    Route::patch('recipes/{recipe}/restore', [RecipeController::class, 'restore'])->name('recipes.restore');
    Route::post('recipe-tags', [RecipeTagController::class, 'store'])->name('recipe-tags.store');
    Route::delete('recipe-tags/{recipeTag}', [RecipeTagController::class, 'destroy'])->name('recipe-tags.destroy');
    Route::get('ingredients', [IngredientController::class, 'index'])->name('ingredients.index');
    Route::post('ingredients', [IngredientController::class, 'store'])->name('ingredients.store');
    Route::patch('ingredients/{ingredient}', [IngredientController::class, 'update'])->name('ingredients.update');
    Route::patch('ingredients/{ingredient}/archive', [IngredientController::class, 'archive'])->name('ingredients.archive');
    Route::patch('ingredients/{ingredient}/restore', [IngredientController::class, 'restore'])->name('ingredients.restore');
    Route::post('ingredients/{ingredient}/alternatives', [IngredientAlternativeController::class, 'store'])->name('ingredients.alternatives.store');
    Route::delete('ingredients/{ingredient}/alternatives/{alternative}', [IngredientAlternativeController::class, 'destroy'])->name('ingredients.alternatives.destroy');
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
