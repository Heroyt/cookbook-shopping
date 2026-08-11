<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\ChangeStoreSectionIcon;
use App\Cookbook\Actions\CreateStoreSection;
use App\Cookbook\Actions\DeleteStoreSection;
use App\Cookbook\Http\Requests\StoreSectionDestroyRequest;
use App\Cookbook\Http\Requests\StoreSectionIconUpdateRequest;
use App\Cookbook\Http\Requests\StoreSectionStoreRequest;
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
        private readonly ChangeStoreSectionIcon $changeStoreSectionIcon,
    ) {}

    public function store(StoreSectionStoreRequest $request): RedirectResponse
    {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            fn (AuthorizedFamilyContext $context): StoreSection => $this->createStoreSection->handle(
                $context,
                $request->storeSectionName(),
                $request->storeSectionColour(),
                $request->storeSectionIcon(),
            ),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section created.')]);

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
