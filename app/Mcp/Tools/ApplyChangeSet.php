<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AgentIntegration\ChangeSets\AgentChangeSetPresenter;
use App\AgentIntegration\ChangeSets\ApplyAgentChangeSet;
use App\Mcp\McpAgentFamilyContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;

#[IsDestructive]
#[IsIdempotent]
#[IsOpenWorld(false)]
final class ApplyChangeSet extends McpAgentTool
{
    protected string $name = 'apply_change_set';

    protected string $title = 'Apply Confirmed Agent Change Set';

    protected string $description = 'Transactionally apply an unchanged preview. Call only after showing the user its effects and warnings and receiving explicit confirmation. Pass the exact preview digest and exactly all warning codes.';

    public function __construct(
        McpAgentFamilyContext $familyContext,
        private readonly ApplyAgentChangeSet $apply,
        private readonly AgentChangeSetPresenter $presenter,
    ) {
        parent::__construct($familyContext);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'change_set_id' => $schema->string()->required()->description('ULID returned by preview_change_set.'),
            'digest' => $schema->string()->min(64)->max(64)->required()->description('Exact SHA-256 digest returned by preview_change_set.'),
            'warning_acknowledgements' => $schema->array()->items($schema->string())->required()
                ->description('Exactly every stable warning code from the preview, or an empty array.'),
            'user_confirmation' => $schema->boolean()->required()
                ->description('Must be true only after the user explicitly confirms this exact preview.'),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond(function () use ($request): array {
            $validated = $request->validate([
                'change_set_id' => ['required', 'string', 'size:26'],
                'digest' => ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'],
                'warning_acknowledgements' => ['present', 'array'],
                'warning_acknowledgements.*' => ['string', 'distinct'],
                'user_confirmation' => ['required', 'accepted'],
            ]);
            $authority = $this->authority($request);
            $this->enforceRateLimit($authority, 'apply', 'apply_per_minute');

            $changeSet = $this->apply->handle(
                $authority->context,
                $authority->credential,
                $this->stringArgument($request, 'change_set_id'),
                $this->stringArgument($request, 'digest'),
                $this->stringListArgument($request, 'warning_acknowledgements'),
            );

            return ['data' => $this->presenter->present($changeSet)];
        });
    }
}
