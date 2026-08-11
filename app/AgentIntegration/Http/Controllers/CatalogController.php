<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Controllers;

use App\AgentIntegration\AgentCredentialFamilyContext;
use App\AgentIntegration\Catalog\CatalogResourceType;
use App\AgentIntegration\Catalog\FamilyCatalog;
use App\AgentIntegration\Http\Requests\CatalogIndexRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CatalogController extends Controller
{
    public function __construct(
        private readonly AgentCredentialFamilyContext $familyContext,
        private readonly FamilyCatalog $catalog,
    ) {}

    public function index(CatalogIndexRequest $request): JsonResponse
    {
        $resources = $this->catalog->list(
            $this->familyContext->resolve($request),
            $request->resourceType(),
            $request->status(),
        );

        return response()->json([
            'data' => $resources,
            'meta' => [
                'count' => count($resources),
                'resource_types' => CatalogResourceType::values(),
            ],
        ]);
    }

    public function show(Request $request, string $resourceType, int $id): JsonResponse
    {
        return response()->json([
            'data' => $this->catalog->detail(
                $this->familyContext->resolve($request),
                CatalogResourceType::from($resourceType),
                $id,
            ),
        ]);
    }
}
