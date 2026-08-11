<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Controllers;

use App\AgentIntegration\Actions\IssueAgentCredential;
use App\AgentIntegration\Actions\RevokeAgentCredential;
use App\AgentIntegration\Actions\RotateAgentCredential;
use App\AgentIntegration\Http\Requests\IssueAgentCredentialRequest;
use App\AgentIntegration\Http\Requests\RevokeAgentCredentialRequest;
use App\AgentIntegration\Http\Requests\RotateAgentCredentialRequest;
use App\AgentIntegration\Models\AgentCredential;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\FamilyMembership;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

final class AgentCredentialController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $currentFamilyScope,
        private readonly IssueAgentCredential $issueAgentCredential,
        private readonly RotateAgentCredential $rotateAgentCredential,
        private readonly RevokeAgentCredential $revokeAgentCredential,
    ) {}

    public function index(RevokeAgentCredentialRequest $request): Response
    {
        $credentials = $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context): Collection {
                $credentials = AgentCredential::query()
                    ->whereBelongsTo($context->family)
                    ->with('revokedBy:id,name')
                    ->latest('id')
                    ->get();
                $currentIssuerIds = array_values(FamilyMembership::query()
                    ->whereBelongsTo($context->family)
                    ->get(['user_id'])
                    ->map(fn (FamilyMembership $membership): int => $membership->user_id)
                    ->all());

                return $credentials->map(fn (AgentCredential $credential): array => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'issuerName' => $credential->issuer_name,
                    'abilities' => $credential->abilities,
                    'status' => $this->status($credential, $currentIssuerIds),
                    'isIssuer' => $credential->tokenable_type === User::class
                        && $credential->tokenable_id === $context->user->id,
                    'createdAt' => $credential->created_at?->toIso8601String(),
                    'expiresAt' => $credential->expires_at?->toIso8601String(),
                    'lastUsedAt' => $credential->last_used_at?->toIso8601String(),
                    'revokedAt' => $credential->revoked_at?->toIso8601String(),
                    'revokedByName' => $credential->revokedBy?->name,
                    'revocationReason' => $credential->revocation_reason,
                    'rotatedToId' => $credential->rotated_to_id,
                ]);
            },
        );

        $passwordConfirmedAt = $request->session()->get('auth.password_confirmed_at');
        $passwordConfirmed = is_int($passwordConfirmedAt)
            && now()->unix() - $passwordConfirmedAt <= Config::integer('auth.password_timeout');

        return Inertia::render('agent-credentials/Index', [
            'credentials' => $credentials,
            'passwordConfirmed' => $passwordConfirmed,
        ]);
    }

    public function confirmed(): RedirectResponse
    {
        return to_route('agent-credentials.index');
    }

    public function store(IssueAgentCredentialRequest $request): RedirectResponse
    {
        $issued = $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context) => $this->issueAgentCredential->handle(
                $context,
                $request->credentialName(),
                $request->credentialAbilities(),
                $request->expiresAt(),
            ),
        );

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __('Agent Credential created.')],
            'agentCredentialSecret' => [
                'name' => $issued->credential->name,
                'secret' => $issued->plainTextSecret,
            ],
        ]);

        return to_route('agent-credentials.index');
    }

    public function rotate(RotateAgentCredentialRequest $request, int $agentCredential): RedirectResponse
    {
        $issued = $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context) => $this->rotateAgentCredential->handle($context, $agentCredential),
        );

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __('Agent Credential rotated.')],
            'agentCredentialSecret' => [
                'name' => $issued->credential->name,
                'secret' => $issued->plainTextSecret,
            ],
        ]);

        return to_route('agent-credentials.index');
    }

    public function destroy(RevokeAgentCredentialRequest $request, int $agentCredential): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): AgentCredential => $this->revokeAgentCredential->handle($context, $agentCredential),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent Credential revoked.')]);

        return to_route('agent-credentials.index');
    }

    /** @param list<int> $currentIssuerIds */
    private function status(AgentCredential $credential, array $currentIssuerIds): string
    {
        if ($credential->revoked_at !== null) {
            return 'revoked';
        }

        if ($credential->expires_at?->isPast() ?? false) {
            return 'expired';
        }

        if ($credential->tokenable_type !== User::class || ! in_array($credential->tokenable_id, $currentIssuerIds, true)) {
            return 'invalidated';
        }

        return 'active';
    }
}
