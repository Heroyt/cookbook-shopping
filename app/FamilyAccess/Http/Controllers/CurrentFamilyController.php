<?php

declare(strict_types=1);

namespace App\FamilyAccess\Http\Controllers;

use App\FamilyAccess\CurrentFamily;
use App\FamilyAccess\Http\Requests\SelectCurrentFamilyRequest;
use App\FamilyAccess\Models\Family;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class CurrentFamilyController extends Controller
{
    public function __construct(private readonly CurrentFamily $currentFamily) {}

    public function update(SelectCurrentFamilyRequest $request, Family $family): RedirectResponse
    {
        $selectedFamily = $this->currentFamily->select($request->authenticatedUser(), $family);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Current Family changed to :family.', ['family' => $selectedFamily->name]),
        ]);

        return back();
    }
}
