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
final class GetFamilyResource extends McpAgentTool
{
    protected string $name = 'get_family_resource';

    protected string $title = 'Get Family Resource';

    protected string $description = 'Read one complete resource from the credential-fixed Family by its exact resource type and identifier.';

    public function __construct(
        McpAgentFamilyContext $familyContext,
        private readonly FamilyCatalog $catalog,
    ) {
        parent::__construct($familyContext);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'resource_type' => $schema->string()->enum(CatalogResourceType::values())->required(),
            'id' => $schema->integer()->min(1)->required(),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        return $this->respond(function () use ($request): array {
            $validated = $request->validate([
                'resource_type' => ['required', 'string', 'in:' . implode(',', CatalogResourceType::values())],
                'id' => ['required', 'integer', 'min:1'],
            ]);
            $authority = $this->authority($request);
            $this->enforceRateLimit($authority, 'catalog', 'catalog_per_minute');

            return ['data' => $this->catalog->detail(
                $authority->context,
                CatalogResourceType::from($this->stringArgument($request, 'resource_type')),
                $this->integerArgument($request, 'id'),
            )];
        });
    }
}
