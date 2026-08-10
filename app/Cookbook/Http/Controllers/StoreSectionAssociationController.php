<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Actions\AttachStoreSection;
use App\Cookbook\Actions\DetachStoreSection;
use App\Cookbook\Actions\ReorderStoreSections;
use App\Cookbook\Http\Requests\StoreSectionAttachRequest;
use App\Cookbook\Http\Requests\StoreSectionDetachRequest;
use App\Cookbook\Http\Requests\StoreSectionReorderRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class StoreSectionAssociationController extends Controller
{
    public function store(
        StoreSectionAttachRequest $request,
        int $store,
        AttachStoreSection $attachStoreSection,
    ): RedirectResponse {
        $attachStoreSection->handle($request->authenticatedUser(), $store, $request->storeSectionId());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section associated.')]);

        return to_route('stores.index');
    }

    public function destroy(
        StoreSectionDetachRequest $request,
        int $store,
        int $storeSection,
        DetachStoreSection $detachStoreSection,
    ): RedirectResponse {
        $detachStoreSection->handle($request->authenticatedUser(), $store, $storeSection);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section removed.')]);

        return to_route('stores.index');
    }

    public function update(
        StoreSectionReorderRequest $request,
        int $store,
        ReorderStoreSections $reorderStoreSections,
    ): RedirectResponse {
        $reorderStoreSections->handle(
            $request->authenticatedUser(),
            $store,
            $request->storeSectionIds(),
            $request->version(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Store Section order saved.')]);

        return to_route('stores.index');
    }
}
