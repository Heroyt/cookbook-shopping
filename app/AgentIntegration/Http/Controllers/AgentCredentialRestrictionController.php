<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Controllers;

use App\AgentIntegration\Actions\RestrictCurrentAgentCredential;
use App\AgentIntegration\AgentCredentialFamilyContext;
use App\AgentIntegration\Http\Requests\RestrictAgentCredentialRequest;
use App\AgentIntegration\Values\AgentCredentialRestrictionResult;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class AgentCredentialRestrictionController extends Controller
{
    public function __construct(
        private readonly AgentCredentialFamilyContext $familyContext,
        private readonly RestrictCurrentAgentCredential $restrictCurrentAgentCredential,
    ) {}

    public function store(RestrictAgentCredentialRequest $request): JsonResponse
    {
        $result = $this->restrictCurrentAgentCredential->handle(
            $this->familyContext->credential($request),
            $request->action(),
            $request->expiresAt(),
        );

        return response()->json(['data' => $this->present($result)]);
    }

    /** @return array<string, bool|int|string|null> */
    private function present(AgentCredentialRestrictionResult $result): array
    {
        $credential = $result->credential;

        return [
            'credential_id' => $credential->id,
            'action' => $result->action->value,
            'status' => $credential->revoked_at === null ? 'active' : 'revoked',
            'expires_at' => $credential->expires_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'revoked_at' => $credential->revoked_at?->utc()->format('Y-m-d\TH:i:s\Z'),
            'changed' => $result->changed,
        ];
    }
}
