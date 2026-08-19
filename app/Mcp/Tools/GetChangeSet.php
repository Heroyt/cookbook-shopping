<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AgentIntegration\ChangeSets\AgentChangeSetHistory;
use App\AgentIntegration\ChangeSets\AgentChangeSetPresenter;
use App\Mcp\McpAgentFamilyContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsOpenWorld(false)]
final class GetChangeSet extends McpAgentTool
{
    protected string $name = 'get_change_set';

    protected string $title = 'Get Family Agent Change Set';

    protected string $description = 'Read a complete preview or immutable applied result from the credential-fixed Family.';

    public function __construct(
        McpAgentFamilyContext $familyContext,
        private readonly AgentChangeSetHistory $history,
        private readonly AgentChangeSetPresenter $presenter,
    ) {
        parent::__construct($familyContext);
    }

    public function schema(JsonSchema $schema): array
    {
        return ['change_set_id' => $schema->string()->min(26)->max(26)->required()];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond(function () use ($request): array {
            $validated = $request->validate(['change_set_id' => ['required', 'string', 'size:26']]);
            $authority = $this->authority($request);
            $this->enforceRateLimit($authority, 'catalog', 'catalog_per_minute');

            return ['data' => $this->presenter->present($this->history->detail(
                $authority->context,
                $this->stringArgument($request, 'change_set_id'),
            ))];
        });
    }
}
