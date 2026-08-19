<?php

declare(strict_types=1);

namespace App\Providers;

use App\AgentIntegration\AgentCredentialRestrictionAction;
use App\AgentIntegration\Models\AgentCredential;
use App\AgentIntegration\OpenApi\AgentOpenApiDocument;
use App\FamilyAccess\CurrentFamily;
use App\FamilyAccess\Models\Family;
use App\Mcp\McpConsentFamilyBinding;
use App\Models\User;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreRoutes();
        Scramble::ignoreDefaultRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(AgentCredential::class);
        Passport::authorizationView(function (array $parameters) {
            $user = $parameters['user'] ?? null;
            $client = $parameters['client'] ?? null;
            $authToken = $parameters['authToken'] ?? null;
            abort_unless($user instanceof User && $client instanceof Client && is_string($authToken), 403);
            $family = app(CurrentFamily::class)->resolve($user);
            $parameters['family'] = $family;
            $parameters['familyBinding'] = $family instanceof Family
                ? app(McpConsentFamilyBinding::class)->issue($user, $family, $client->id, $authToken)
                : null;

            return response()->view('mcp.authorize', $parameters);
        });
        Passport::tokensExpireIn(now()->addDays(
            Config::integer('agent-integration.credentials.default_expiry_days'),
        ));
        Passport::refreshTokensExpireIn(now()->addDays(
            Config::integer('agent-integration.credentials.default_expiry_days'),
        ));
        Scramble::configure()
            ->expose('/docs/agent-api/v1', '/docs/agent-api/v1/openapi.json')
            ->withDocumentTransformers([AgentOpenApiDocument::class]);
        $this->configureAgentRateLimits();

        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    private function configureAgentRateLimits(): void
    {
        RateLimiter::for('agent-catalog', fn (Request $request): Limit => Limit::perMinute(
            Config::integer('agent-integration.rates.catalog_per_minute'),
        )->by($this->agentRateKey($request)));
        RateLimiter::for('agent-preview', fn (Request $request): Limit => Limit::perMinute(
            Config::integer('agent-integration.rates.preview_per_minute'),
        )->by($this->agentRateKey($request)));
        RateLimiter::for('agent-apply', fn (Request $request): Limit => Limit::perMinute(
            Config::integer('agent-integration.rates.apply_per_minute'),
        )->by($this->agentRateKey($request)));
        RateLimiter::for('agent-credential-restriction', fn (Request $request): Limit => Limit::perMinute(
            Config::integer('agent-integration.rates.credential_restriction_per_minute'),
        )->by($this->agentRateKey($request) . ':' . $this->agentRestrictionActionRateKey($request)));
        RateLimiter::for('agent-media-upload', fn (Request $request): Limit => Limit::perMinute(
            Config::integer('agent-integration.rates.media_upload_per_minute'),
        )->by($this->agentRateKey($request)));
    }

    private function agentRateKey(Request $request): string
    {
        $credential = $request->user()?->currentAccessToken();

        return $credential instanceof AgentCredential
            ? 'agent-credential:' . $credential->id
            : 'agent-source:' . $request->ip();
    }

    private function agentRestrictionActionRateKey(Request $request): string
    {
        $action = $request->input('action');

        if ( ! is_string($action) || AgentCredentialRestrictionAction::tryFrom($action) === null) {
            return 'invalid';
        }

        if ($action === AgentCredentialRestrictionAction::Revoke->value && $request->all() !== ['action' => $action]) {
            return 'invalid';
        }

        return $action;
    }
}
