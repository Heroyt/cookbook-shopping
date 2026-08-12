<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\ManageEntityMedia;
use App\Cookbook\Exceptions\EntityMediaRejected;
use App\Cookbook\Http\Requests\EntityMediaShowRequest;
use App\Cookbook\Http\Requests\EntityMediaStoreRequest;
use App\Cookbook\Values\EntityMediaType;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\CurrentFamilyScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class EntityMediaController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $currentFamilyScope,
        private readonly ManageEntityMedia $media,
    ) {}

    public function store(EntityMediaStoreRequest $request, string $mediaType, int $entity): RedirectResponse
    {
        $type = EntityMediaType::tryFrom($mediaType) ?? abort(404);
        try {
            $this->currentFamilyScope->withinContext(
                $request->authenticatedUser(),
                function (AuthorizedFamilyContext $context) use ($entity, $request, $type): void {
                    $this->media->store($context, $type, $entity, $request->uploadedImage());
                },
            );
        } catch (EntityMediaRejected $exception) {
            throw ValidationException::withMessages([
                'image' => __($exception->failure->message()),
            ]);
        }
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

        return $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): StreamedResponse => $this->media->response($context, $type, $entity, $variant),
        );
    }
}
