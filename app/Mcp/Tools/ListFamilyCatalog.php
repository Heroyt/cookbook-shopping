<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\Catalog\FamilyCatalog;
use App\Mcp\McpAgentFamilyContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsOpenWorld(false)]
final class ListFamilyCatalog extends McpAgentTool
{
    protected string $name = 'list_family_catalog';

    protected string $title = 'List Family Catalog';

    protected string $description = 'List complete resources from the credential-fixed Family. Use before creating recipes to detect duplicate recipes and map existing ingredients, tags, stores, and sections.';

    public function __construct(
        McpAgentFamilyContext $familyContext,
        private readonly FamilyCatalog $catalog,
    ) {
        parent::__construct($familyContext);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource_type' => $schema->string()->enum(CatalogResourceType::values())
                ->description('Optional resource collection. Omit to return every collection.'),
            'status' => $schema->string()->enum(['active', 'archived'])
                ->description('Optional active or archived filter.'),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond(function () use ($request): array {
            $validated = $request->validate([
                'resource_type' => ['nullable', 'string', 'in:' . implode(',', CatalogResourceType::values())],
                'status' => ['nullable', 'string', 'in:active,archived'],
            ]);
            $resourceTypeValue = $this->nullableStringArgument($request, 'resource_type');
            $status = $this->nullableStringArgument($request, 'status');
            $resourceType = $resourceTypeValue !== null
                ? CatalogResourceType::from($resourceTypeValue)
                : null;
            $authority = $this->authority($request);
            $this->enforceRateLimit($authority, 'catalog', 'catalog_per_minute');

            return ['data' => $this->catalog->list(
                $authority->context,
                $resourceType,
                $status,
            )];
        });
    }
}
