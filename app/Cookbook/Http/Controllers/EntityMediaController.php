<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\ManageEntityMedia;
use App\Cookbook\Http\Requests\EntityMediaShowRequest;
use App\Cookbook\Http\Requests\EntityMediaStoreRequest;
use App\Cookbook\Values\EntityMediaType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class EntityMediaController extends Controller
{
    public function __construct(private readonly ManageEntityMedia $media) {}

    public function store(EntityMediaStoreRequest $request, string $mediaType, int $entity): RedirectResponse
    {
        $type = EntityMediaType::tryFrom($mediaType) ?? abort(404);
        $this->media->store($request->authenticatedUser(), $type, $entity, $request->uploadedImage());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Image saved.')]);

        return to_route($type->managementRoute());
    }

    public function show(
        EntityMediaShowRequest $request,
        string $mediaType,
        int $entity,
        string $variant,
    ): StreamedResponse {
        $type = EntityMediaType::tryFrom($mediaType) ?? abort(404);

        return $this->media->response($request->authenticatedUser(), $type, $entity, $variant);
    }
}
