<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Controllers;

use App\AgentIntegration\Actions\RestrictCurrentAgentCredential;
use App\AgentIntegration\AgentCredentialFamilyContext;
use App\AgentIntegration\AgentCredentialRestrictionPresenter;
use App\AgentIntegration\Http\Requests\RestrictAgentCredentialRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class AgentCredentialRestrictionController extends Controller
{
    public function __construct(
        private readonly AgentCredentialFamilyContext $familyContext,
        private readonly RestrictCurrentAgentCredential $restrictCurrentAgentCredential,
        private readonly AgentCredentialRestrictionPresenter $presenter,
    ) {}

    public function store(RestrictAgentCredentialRequest $request): JsonResponse
    {
        $result = $this->restrictCurrentAgentCredential->handle(
            $this->familyContext->credential($request),
            $request->action(),
            $request->expiresAt(),
        );

        return response()->json(['data' => $this->presenter->present($result)]);
    }
}
