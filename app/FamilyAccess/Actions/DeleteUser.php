<?php

declare(strict_types=1);

namespace App\FamilyAccess\Actions;

use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteUser
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $families = Family::query()
                ->whereIn('id', $lockedUser->familyMemberships()->select('family_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $families->loadCount('memberships');

            foreach ($families as $family) {
                if ($family->memberships_count <= 1) {
                    throw ValidationException::withMessages([
                        'account' => __('Account deletion is unavailable while you are the final member of a Family. Family member management and Family deletion are not available yet.'),
                    ]);
                }
            }

            $lockedUser->forceFill(['remember_token' => null])->save();
            $lockedUser->delete();
        }, 3);
    }
}
