<?php

declare(strict_types=1);

use App\Http\Controllers\Mcp\ApproveMcpAuthorizationController;
use App\Http\Middleware\EnsureMcpAgentAuthority;
use App\Mcp\Servers\AgentServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Http\Controllers\OAuthRegisterController;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Controllers\DenyAuthorizationController;
use Laravel\Passport\Http\Middleware\CheckToken;

Mcp::oauthRoutes();

Route::post('/oauth/register', OAuthRegisterController::class)
    ->middleware('throttle:10,1');
Route::post('/oauth/token', [AccessTokenController::class, 'issueToken'])
    ->middleware('throttle:60,1')
    ->name('passport.token');
Route::get('/oauth/authorize', [AuthorizationController::class, 'authorize'])
    ->middleware(['web', 'auth', 'password.confirm'])
    ->name('passport.authorizations.authorize');
Route::post('/oauth/authorize', ApproveMcpAuthorizationController::class)
    ->middleware(['web', 'auth', 'password.confirm'])
    ->name('passport.authorizations.approve');
Route::delete('/oauth/authorize', [DenyAuthorizationController::class, 'deny'])
    ->middleware(['web', 'auth', 'password.confirm'])
    ->name('passport.authorizations.deny');

Mcp::web('/mcp', AgentServer::class)
    ->middleware([
        'auth:api',
        CheckToken::using('mcp:use'),
        EnsureMcpAgentAuthority::class,
        'throttle:240,1',
    ]);
