<?php

declare(strict_types=1);

use App\AgentIntegration\Http\AgentApiErrorResponse;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'abilities' => CheckAbilities::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $exception, Request $request): ?JsonResponse {
            if ( ! $request->is('api/v1/*')) {
                return null;
            }

            return AgentApiErrorResponse::make(
                'authentication_required',
                'A valid Agent Credential is required.',
                401,
            );
        });
        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request): ?JsonResponse {
            if ( ! $request->is('api/v1/*')) {
                return null;
            }

            return AgentApiErrorResponse::make(
                'ability_required',
                'The Agent Credential does not have every required ability.',
                403,
                details: ['required_abilities' => ['content:read']],
            );
        });
        $exceptions->render(function (ValidationException $exception, Request $request): ?JsonResponse {
            if ( ! $request->is('api/v1/*')) {
                return null;
            }

            return AgentApiErrorResponse::make(
                'validation_failed',
                'The request document is invalid.',
                422,
                details: ['fields' => $exception->errors()],
            );
        });
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $exception, Request $request): ?JsonResponse {
            if ( ! $request->is('api/v1/*')) {
                return null;
            }

            return AgentApiErrorResponse::make(
                'resource_not_found',
                'The requested Family resource was not found.',
                404,
            );
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, mixed $exception, Request $request): Response {
            if ($response->getStatusCode() !== 419 || $request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __('Your session expired. Refresh the page and try again.'),
            ]);

            return back();
        });
    })->create();
