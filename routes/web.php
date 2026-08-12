<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (Request $request): RedirectResponse => to_route($request->user() === null ? 'login' : 'dashboard'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
});

require __DIR__ . '/family-access.php';
require __DIR__ . '/cookbook.php';
require __DIR__ . '/meal-planning.php';
require __DIR__ . '/agent-integration.php';
require __DIR__ . '/settings.php';
