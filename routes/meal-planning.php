<?php

declare(strict_types=1);

use App\MealPlanning\Http\Controllers\CalendarController;
use App\MealPlanning\Http\Controllers\SavedShoppingListSourceController;
use App\MealPlanning\Http\Controllers\SimplePlanController;
use App\ShoppingGeneration\Http\Controllers\SavedShoppingListController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('shopping-list-history', [SavedShoppingListController::class, 'index'])
        ->name('shopping-list-history.index');
    Route::get('shopping-list-history/{snapshot}', [SavedShoppingListController::class, 'show'])
        ->whereNumber('snapshot')
        ->name('shopping-list-history.show');
    Route::post('shopping-list-history/simple-plan', [SavedShoppingListSourceController::class, 'storeSimplePlan'])
        ->block(10, 10)
        ->name('shopping-list-history.simple-plan.store');
    Route::post('shopping-list-history/calendar', [SavedShoppingListSourceController::class, 'storeCalendar'])
        ->block(10, 10)
        ->name('shopping-list-history.calendar.store');
    Route::delete('shopping-list-history/{snapshot}', [SavedShoppingListController::class, 'destroy'])
        ->whereNumber('snapshot')
        ->block(10, 10)
        ->name('shopping-list-history.destroy');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::post('calendar/entries', [CalendarController::class, 'store'])
        ->block(10, 10)
        ->name('calendar.entries.store');
    Route::put('calendar/entries/{entry}', [CalendarController::class, 'update'])
        ->whereNumber('entry')
        ->block(10, 10)
        ->name('calendar.entries.update');
    Route::delete('calendar/entries/{entry}', [CalendarController::class, 'destroy'])
        ->whereNumber('entry')
        ->block(10, 10)
        ->name('calendar.entries.destroy');
    Route::get('calendar/generated', [CalendarController::class, 'generated'])->name('calendar.generated');
    Route::post('calendar/generate', [CalendarController::class, 'generate'])
        ->block(10, 10)
        ->name('calendar.generate');
    Route::post('calendar/alternatives', [CalendarController::class, 'storeAlternative'])
        ->block(10, 10)
        ->name('calendar.alternatives.store');
    Route::delete('calendar/alternatives/{originalIngredient}', [CalendarController::class, 'destroyAlternative'])
        ->whereNumber('originalIngredient')
        ->block(10, 10)
        ->name('calendar.alternatives.destroy');

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
