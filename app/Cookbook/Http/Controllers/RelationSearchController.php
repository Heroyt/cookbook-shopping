<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Controllers;

use App\Cookbook\Http\Requests\RelationSearchRequest;
use App\Cookbook\Queries\CurrentFamilyRelationSearch;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

final class RelationSearchController extends Controller
{
    public function __construct(
        private readonly CurrentFamilyScope $currentFamilyScope,
        private readonly CurrentFamilyRelationSearch $relationSearch,
    ) {}

    public function recipes(RelationSearchRequest $request): JsonResponse
    {
        return response()->json($this->currentFamilyScope->within(
            $request->authenticatedUser(),
            fn (Family $family): array => $this->relationSearch->recipes(
                $family,
                $request->searchTerm(),
                $request->resultLimit(),
                $request->searchCursor(),
            ),
        ));
    }

    public function stores(RelationSearchRequest $request): JsonResponse
    {
        return response()->json($this->currentFamilyScope->within(
            $request->authenticatedUser(),
            fn (Family $family): array => $this->relationSearch->stores(
                $family,
                $request->searchTerm(),
                $request->resultLimit(),
                $request->searchCursor(),
            ),
        ));
    }

    public function storeSections(RelationSearchRequest $request): JsonResponse
    {
        return response()->json($this->currentFamilyScope->within(
            $request->authenticatedUser(),
            fn (Family $family): array => $this->relationSearch->storeSections(
                $family,
                $request->storeId(),
                $request->searchTerm(),
                $request->resultLimit(),
                $request->searchCursor(),
            ),
        ));
    }
}
