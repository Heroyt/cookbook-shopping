<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AgentIntegration\ChangeSets\AgentChangeSetPresenter;
use App\AgentIntegration\ChangeSets\PreviewAgentChangeSet;
use App\Mcp\McpAgentFamilyContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use JsonException;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsDestructive(false)]
#[IsIdempotent]
#[IsOpenWorld(false)]
final class PreviewChangeSet extends McpAgentTool
{
    protected string $name = 'preview_change_set';

    protected string $title = 'Preview Agent Change Set';

    protected string $description = 'Validate and preview a version 1 Agent Change Set without changing domain data. Reuse client_request_id only for the same canonical document. Inspect every effect and warning before asking the user to confirm apply.';

    public function __construct(
        McpAgentFamilyContext $familyContext,
        private readonly PreviewAgentChangeSet $preview,
        private readonly AgentChangeSetPresenter $presenter,
    ) {
        parent::__construct($familyContext);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'document' => $schema->object()->required()->description(
                'Exact version 1 Change Set document. It requires version=1, a unique client_request_id, and a non-empty operations array. Each operation requires operation_id, resource_type, action, and action-specific fields. Read the public OpenAPI contract when composing operation data.',
            ),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond(function () use ($request): array {
            $validated = $request->validate(['document' => ['required', 'array']]);
            $authority = $this->authority($request);
            $this->enforceRateLimit($authority, 'preview', 'preview_per_minute');
            $document = $this->objectArgument($request, 'document');

            try {
                $payloadBytes = strlen(json_encode($document, JSON_THROW_ON_ERROR));
            } catch (JsonException) {
                $payloadBytes = PHP_INT_MAX;
            }

            $previewed = $this->preview->handle(
                $authority->context,
                $authority->credential,
                $document,
                $payloadBytes,
            );

            return ['data' => $this->presenter->present($previewed->changeSet)];
        });
    }
}
