<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\AttachStoreSection;
use App\Cookbook\Actions\ChangeStoreSectionColour;
use App\Cookbook\Actions\ChangeStoreSectionIcon;
use App\Cookbook\Actions\CreateStoreSection;
use App\Cookbook\Actions\DeleteStoreSection;
use App\Cookbook\Actions\UpdateStoreSection;
use App\Cookbook\Http\Requests\StoreSectionColourUpdateRequest;
use App\Cookbook\Http\Requests\StoreSectionDestroyRequest;
use App\Cookbook\Http\Requests\StoreSectionIconUpdateRequest;
use App\Cookbook\Http\Requests\StoreSectionStoreRequest;
use App\Cookbook\Http\Requests\StoreSectionUpdateRequest;
use App\Cookbook\Models\StoreSection;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\CurrentFamilyScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class StoreSectionController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $currentFamilyScope,
        private readonly CreateStoreSection $createStoreSection,
        private readonly DeleteStoreSection $deleteStoreSection,
        private readonly ChangeStoreSectionColour $changeStoreSectionColour,
        private readonly ChangeStoreSectionIcon $changeStoreSectionIcon,
        private readonly UpdateStoreSection $updateStoreSection,
        private readonly AttachStoreSection $attachStoreSection,
    ) {}

    public function store(StoreSectionStoreRequest $request): RedirectResponse
    {
        $storeSection = $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($request): StoreSection {
                $storeSection = $this->createStoreSection->handle(
                    $context,
                    $request->storeSectionName(),
                    $request->storeSectionColour(),
                    $request->storeSectionIcon(),
                );

                if ($request->storeId() !== null) {
                    $this->attachStoreSection->handle($context, $request->storeId(), $storeSection->id);
                }

                return $storeSection;
            },
        );

        Inertia::flash([
            'toast' => ['type' => 'success', 'message' => __('Store Section created.')],
            'createdStoreSection' => [
                'id' => $storeSection->id,
                'name' => $storeSection->name,
                'colour' => $storeSection->colour,
                'icon' => $storeSection->icon->value,
                'iconUrl' => null,
            ],
        ]);

        return $request->layered() ? back() : to_route('stores.index');
    }

    public function updateColour(StoreSectionColourUpdateRequest $request, int $storeSection): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): StoreSection => $this->changeStoreSectionColour->handle(
                $context,
                $storeSection,
                $request->storeSectionColour(),
            ),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section colour updated.')]);

        return back();
    }

    public function update(StoreSectionUpdateRequest $request, int $storeSection): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): StoreSection => $this->updateStoreSection->handle(
                $context,
                $storeSection,
                $request->storeSectionName(),
                $request->storeSectionColour(),
                $request->storeSectionIcon(),
            ),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section updated.')]);

        return to_route('stores.index');
    }

    public function updateIcon(StoreSectionIconUpdateRequest $request, int $storeSection): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): StoreSection => $this->changeStoreSectionIcon->handle(
                $context,
                $storeSection,
                $request->storeSectionIcon(),
            ),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section icon updated.')]);

        return to_route('stores.index');
    }

    public function destroy(StoreSectionDestroyRequest $request, int $storeSection): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($storeSection): void {
                $this->deleteStoreSection->handle($context, $storeSection);
            },
            1,
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section deleted.')]);

        return to_route('stores.index');
    }
}
