<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\ChangeSets\AgentChangeSetHistory;
use App\AgentIntegration\ChangeSets\AgentChangeSetPresenter;
use App\AgentIntegration\Models\AgentChangeSet;
use App\Mcp\McpAgentFamilyContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsOpenWorld(false)]
final class ListChangeSets extends McpAgentTool
{
    protected string $name = 'list_change_sets';

    protected string $title = 'List Family Agent Change Sets';

    protected string $description = 'List previewed and immutable historical Agent Change Sets visible to the credential-fixed Family.';

    public function __construct(
        McpAgentFamilyContext $familyContext,
        private readonly AgentChangeSetHistory $history,
        private readonly AgentChangeSetPresenter $presenter,
    ) {
        parent::__construct($familyContext);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->enum(['previewed', 'applied', 'expired', 'invalidated', 'stale']),
            'resource_type' => $schema->string()->enum(CatalogResourceType::values()),
            'outcome' => $schema->string()->description('Optional exact outcome filter.'),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond(function () use ($request): array {
            $validated = $request->validate([
                'status' => ['nullable', 'string', 'in:previewed,applied,expired,invalidated,stale'],
                'resource_type' => ['nullable', 'string', 'in:' . implode(',', CatalogResourceType::values())],
                'outcome' => ['nullable', 'string', 'max:255'],
            ]);
            $authority = $this->authority($request);
            $this->enforceRateLimit($authority, 'catalog', 'catalog_per_minute');
            $filters = [
                'status' => $this->nullableStringArgument($request, 'status'),
                'resource_type' => $this->nullableStringArgument($request, 'resource_type'),
                'outcome' => $this->nullableStringArgument($request, 'outcome'),
            ];
            $changeSets = $this->history->list($authority->context, $filters);

            return ['data' => $changeSets
                ->map(fn (AgentChangeSet $changeSet): array => $this->presenter->present($changeSet))
                ->values()
                ->all()];
        });
    }
}
