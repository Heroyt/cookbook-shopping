<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\AttachStoreSection;
use App\Cookbook\Actions\DetachStoreSection;
use App\Cookbook\Actions\ReorderStoreSections;
use App\Cookbook\Http\Requests\StoreSectionAttachRequest;
use App\Cookbook\Http\Requests\StoreSectionDetachRequest;
use App\Cookbook\Http\Requests\StoreSectionReorderRequest;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\CurrentFamilyScope;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class StoreSectionAssociationController extends Controller
{
    public function __construct(private readonly CurrentFamilyScope $currentFamilyScope) {}

    public function store(
        StoreSectionAttachRequest $request,
        int $store,
        AttachStoreSection $attachStoreSection,
    ): RedirectResponse {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($attachStoreSection, $request, $store): void {
                $attachStoreSection->handle($context, $store, $request->storeSectionId());
            },
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section associated.')]);

        return to_route('stores.index');
    }

    public function destroy(
        StoreSectionDetachRequest $request,
        int $store,
        int $storeSection,
        DetachStoreSection $detachStoreSection,
    ): RedirectResponse {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($detachStoreSection, $store, $storeSection): void {
                $detachStoreSection->handle($context, $store, $storeSection);
            },
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section removed.')]);

        return to_route('stores.index');
    }

    public function update(
        StoreSectionReorderRequest $request,
        int $store,
        ReorderStoreSections $reorderStoreSections,
    ): RedirectResponse {
        $this->currentFamilyScope->withinContext(
            $request->authenticatedUser(),
            function (AuthorizedFamilyContext $context) use ($request, $reorderStoreSections, $store): void {
                $reorderStoreSections->handle(
                    $context,
                    $store,
                    $request->storeSectionIds(),
                    $request->version(),
                );
            },
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section order saved.')]);

        return to_route('stores.index');
    }
}
