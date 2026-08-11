<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Controllers;

use App\AgentIntegration\AgentCredentialFamilyContext;
use App\AgentIntegration\ChangeSets\AgentChangeSetPresenter;
use App\AgentIntegration\ChangeSets\ApplyAgentChangeSet;
use App\AgentIntegration\ChangeSets\PreviewAgentChangeSet;
use App\AgentIntegration\Http\Requests\ApplyAgentChangeSetRequest;
use App\AgentIntegration\Http\Requests\PreviewAgentChangeSetRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class AgentChangeSetController extends Controller
{
    public function __construct(
        private readonly AgentCredentialFamilyContext $familyContext,
        private readonly PreviewAgentChangeSet $previewAgentChangeSet,
        private readonly ApplyAgentChangeSet $applyAgentChangeSet,
        private readonly AgentChangeSetPresenter $presenter,
    ) {}

    public function store(PreviewAgentChangeSetRequest $request): JsonResponse
    {
        $previewed = $this->previewAgentChangeSet->handle(
            $this->familyContext->resolve($request),
            $this->familyContext->credential($request),
            $request->document(),
            strlen($request->getContent()),
        );

        return response()->json(
            ['data' => $this->presenter->present($previewed->changeSet)],
            $previewed->created ? 201 : 200,
        );
    }

    public function apply(ApplyAgentChangeSetRequest $request, string $changeSet): JsonResponse
    {
        $applied = $this->applyAgentChangeSet->handle(
            $this->familyContext->resolve($request),
            $this->familyContext->credential($request),
            $changeSet,
            $request->digest(),
            $request->warningAcknowledgements(),
        );

        return response()->json(['data' => $this->presenter->present($applied)]);
    }
}
