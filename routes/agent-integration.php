<?php

declare(strict_types=1);

use App\AgentIntegration\Http\Controllers\AgentChangeSetHistoryController;
use App\AgentIntegration\Http\Controllers\AgentCredentialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('agent-change-history', [AgentChangeSetHistoryController::class, 'index'])->name('agent-change-sets.index');
    Route::get('agent-change-history/{agentChangeSet}', [AgentChangeSetHistoryController::class, 'show'])->name('agent-change-sets.show');
    Route::delete('agent-change-history/{agentChangeSet}', [AgentChangeSetHistoryController::class, 'destroy'])->name('agent-change-sets.destroy');
    Route::get('agent-access', [AgentCredentialController::class, 'index'])->name('agent-credentials.index');
    Route::get('agent-access/password-confirmation', [AgentCredentialController::class, 'confirmed'])
        ->middleware('password.confirm')
        ->name('agent-credentials.password-confirmation');
    Route::post('agent-access', [AgentCredentialController::class, 'store'])
        ->middleware('password.confirm')
        ->name('agent-credentials.store');
    Route::post('agent-access/{agentCredential}/rotate', [AgentCredentialController::class, 'rotate'])
        ->middleware('password.confirm')
        ->whereNumber('agentCredential')
        ->name('agent-credentials.rotate');
    Route::delete('agent-access/{agentCredential}', [AgentCredentialController::class, 'destroy'])
        ->whereNumber('agentCredential')
        ->name('agent-credentials.destroy');
});
