<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\OpenApi\AgentOperationOpenApiSchemas;
use App\Mcp\McpAgentFamilyContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[IsOpenWorld(false)]
final class GetChangeSetExamples extends McpAgentTool
{
    protected string $name = 'get_change_set_examples';

    protected string $title = 'Get Change Set Examples';

    protected string $description = 'Return authoritative version 1 Change Set examples generated from the same schema source as the public OpenAPI contract. Use these examples before composing create, update, archive, restore, or delete operations.';

    public function __construct(
        McpAgentFamilyContext $familyContext,
        private readonly AgentOperationOpenApiSchemas $schemas,
    ) {
        parent::__construct($familyContext);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource_type' => $schema->string()->enum(CatalogResourceType::values())
                ->description('Optional resource type filter.'),
            'action' => $schema->string()->enum(['create', 'update', 'archive', 'restore', 'delete'])
                ->description('Optional operation action filter.'),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond(function () use ($request): array {
            $validated = $request->validate([
                'resource_type' => ['nullable', 'string', 'in:' . implode(',', CatalogResourceType::values())],
                'action' => ['nullable', 'string', 'in:create,update,archive,restore,delete'],
            ]);
            $authority = $this->authority($request);
            $this->enforceRateLimit($authority, 'catalog', 'catalog_per_minute');

            $documents = array_values(array_filter(
                $this->schemas->documentExamples(),
                static function (array $document) use ($validated): bool {
                    $operations = $document['operations'] ?? null;
                    if ( ! is_array($operations)) {
                        return false;
                    }
                    $operation = $operations[0] ?? null;
                    if ( ! is_array($operation)) {
                        return false;
                    }

                    return ( ! isset($validated['resource_type']) || ($operation['resource_type'] ?? null) === $validated['resource_type'])
                        && ( ! isset($validated['action']) || ($operation['action'] ?? null) === $validated['action']);
                },
            ));

            return [
                'data' => $documents,
                'openapi_resource_uri' => 'cookbook://agent-api/v1/openapi.json',
            ];
        });
    }
}
