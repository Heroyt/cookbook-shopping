<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

Route::get('/', fn (): Response => Inertia::render('Welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', fn (): Response => Inertia::render('Dashboard'))->name('dashboard');
});

require __DIR__ . '/settings.php';
