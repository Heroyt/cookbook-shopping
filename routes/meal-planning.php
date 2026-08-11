<?php

declare(strict_types=1);

use App\MealPlanning\Http\Controllers\SimplePlanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('simple-plan', [SimplePlanController::class, 'index'])->name('simple-plan.index');
    Route::get('simple-plan/generated', [SimplePlanController::class, 'generated'])->name('simple-plan.generated');
    Route::post('simple-plan/selections', [SimplePlanController::class, 'store'])
        ->block(10, 10)
        ->name('simple-plan.selections.store');
    Route::delete('simple-plan/selections/{recipe}', [SimplePlanController::class, 'destroy'])
        ->whereNumber('recipe')
        ->block(10, 10)
        ->name('simple-plan.selections.destroy');
    Route::post('simple-plan/generate', [SimplePlanController::class, 'generate'])
        ->block(10, 10)
        ->name('simple-plan.generate');
    Route::post('simple-plan/alternatives', [SimplePlanController::class, 'storeAlternative'])
        ->block(10, 10)
        ->name('simple-plan.alternatives.store');
    Route::delete('simple-plan/alternatives/{originalIngredient}', [SimplePlanController::class, 'destroyAlternative'])
        ->whereNumber('originalIngredient')
        ->block(10, 10)
        ->name('simple-plan.alternatives.destroy');
});
