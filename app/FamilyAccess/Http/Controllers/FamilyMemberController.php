<?php

declare(strict_types=1);

namespace App\FamilyAccess\Http\Controllers;

use App\FamilyAccess\Actions\AddFamilyMember;
use App\FamilyAccess\Actions\RemoveFamilyMember;
use App\FamilyAccess\CurrentFamily;
use App\FamilyAccess\Http\Requests\AddFamilyMemberRequest;
use App\FamilyAccess\Http\Requests\RemoveFamilyMemberRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final class FamilyMemberController extends Controller
{
    public function __construct(
        private readonly CurrentFamily $currentFamily,
        private readonly AddFamilyMember $addFamilyMember,
        private readonly RemoveFamilyMember $removeFamilyMember,
    ) {}

    public function store(AddFamilyMemberRequest $request): RedirectResponse
    {
        $family = $this->currentFamily->resolve($request->authenticatedUser()) ?? abort(404);

        $this->addFamilyMember->handle($request->authenticatedUser(), $family, $request->memberEmail());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Family member added.')]);

        return to_route('families.index');
    }

    public function destroy(
        RemoveFamilyMemberRequest $request,
        User $user,
    ): RedirectResponse {
        $family = $this->currentFamily->resolve($request->authenticatedUser()) ?? abort(404);

        $this->removeFamilyMember->handle($request->authenticatedUser(), $family, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Family Membership removed.')]);

        return to_route('families.index');
    }
}
