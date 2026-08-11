<?php

declare(strict_types=1);

use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\Http\Controllers\AgentChangeSetController;
use App\AgentIntegration\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'abilities:content:read'])->group(function (): void {
    Route::get('catalog', [CatalogController::class, 'index'])
        ->middleware('throttle:agent-catalog')
        ->name('api.v1.catalog.index');
    Route::get('catalog/{resourceType}/{id}', [CatalogController::class, 'show'])
        ->whereIn('resourceType', CatalogResourceType::values())
        ->whereNumber('id')
        ->middleware('throttle:agent-catalog')
        ->name('api.v1.catalog.show');
    Route::post('change-sets', [AgentChangeSetController::class, 'store'])
        ->middleware(['agent.payload', 'throttle:agent-preview'])
        ->name('api.v1.change-sets.store');
    Route::post('change-sets/{changeSet}/apply', [AgentChangeSetController::class, 'apply'])
        ->middleware('throttle:agent-apply')
        ->name('api.v1.change-sets.apply');
});
