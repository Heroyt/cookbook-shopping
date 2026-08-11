<?php

declare(strict_types=1);

use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'abilities:content:read'])->group(function (): void {
    Route::get('catalog', [CatalogController::class, 'index'])->name('api.v1.catalog.index');
    Route::get('catalog/{resourceType}/{id}', [CatalogController::class, 'show'])
        ->whereIn('resourceType', CatalogResourceType::values())
        ->whereNumber('id')
        ->name('api.v1.catalog.show');
});
