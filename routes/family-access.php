<?php

declare(strict_types=1);

use App\FamilyAccess\Http\Controllers\CurrentFamilyController;
use App\FamilyAccess\Http\Controllers\FamilyController;
use App\FamilyAccess\Http\Controllers\FamilyMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('families', [FamilyController::class, 'index'])->name('families.index');
    Route::get('families/create', [FamilyController::class, 'create'])->name('families.create');
    Route::post('families', [FamilyController::class, 'store'])->name('families.store');
    Route::put('current-family/{family}', [CurrentFamilyController::class, 'update'])->name('current-family.update');
    Route::post('current-family/members', [FamilyMemberController::class, 'store'])->name('current-family.members.store');
    Route::delete('current-family/members/{user}', [FamilyMemberController::class, 'destroy'])->name('current-family.members.destroy');
    Route::delete('current-family', [FamilyController::class, 'destroy'])->name('current-family.destroy');
});
