<?php

declare(strict_types=1);

namespace App\AgentIntegration\Http\Controllers;

use App\AgentIntegration\Actions\UploadAgentEntityMedia;
use App\AgentIntegration\AgentCredentialFamilyContext;
use App\AgentIntegration\Http\Requests\AgentMediaUploadRequest;
use App\AgentIntegration\Media\AgentMediaResourceType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class AgentMediaController extends Controller
{
    public function __construct(
        private readonly AgentCredentialFamilyContext $familyContext,
        private readonly UploadAgentEntityMedia $uploadAgentEntityMedia,
    ) {}

    public function store(AgentMediaUploadRequest $request, string $resourceType, int $id): JsonResponse
    {
        $type = AgentMediaResourceType::from($resourceType);
        $this->uploadAgentEntityMedia->handle(
            $this->familyContext->resolve($request),
            $this->familyContext->credential($request),
            $type,
            $id,
            $request->uploadedImage(),
        );

        return response()->json([
            'data' => [
                'resource_type' => $type->value,
                'id' => $id,
                'media_type' => $type->mediaType()->value,
            ],
        ]);
    }
}
