<?php

declare(strict_types=1);

namespace App\FamilyAccess;

use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class CurrentFamily
{
    public function resolve(User $user): ?Family
    {
        return DB::transaction(function () use ($user): ?Family {
            $lockedUser = $this->lockUser($user);
            $currentFamily = $this->membershipFamily($lockedUser, $lockedUser->current_family_id);

            if ($currentFamily instanceof Family) {
                return $currentFamily;
            }

            $fallbackFamily = $lockedUser
                ->families()
                ->select(['families.id', 'families.name'])
                ->orderBy('families.id')
                ->first();

            $lockedUser->forceFill(['current_family_id' => $fallbackFamily?->id])->save();

            return $fallbackFamily;
        }, 3);
    }

    public function select(User $user, Family $family): Family
    {
        return DB::transaction(function () use ($user, $family): Family {
            $lockedUser = $this->lockUser($user);
            $selectedFamily = Family::query()
                ->whereKey($family->getKey())
                ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', $lockedUser->id))
                ->lockForUpdate()
                ->firstOrFail();

            $lockedUser->forceFill(['current_family_id' => $selectedFamily->id])->save();

            return $selectedFamily;
        }, 3);
    }

    private function lockUser(User $user): User
    {
        return User::query()
            ->whereKey($user->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function membershipFamily(User $user, ?int $familyId): ?Family
    {
        if ($familyId === null) {
            return null;
        }

        return Family::query()
            ->whereKey($familyId)
            ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', $user->id))
            ->first();
    }
}
