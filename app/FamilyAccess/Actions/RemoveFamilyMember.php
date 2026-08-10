<?php

declare(strict_types=1);

namespace App\FamilyAccess\Actions;

use App\FamilyAccess\CurrentFamily;
use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\FamilyAccess\UserRowLocks;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RemoveFamilyMember
{
    public function __construct(
        private readonly CurrentFamily $currentFamily,
        private readonly UserRowLocks $userRowLocks,
    ) {}

    public function handle(User $actor, Family $currentFamily, User $target): void
    {
        $removedCurrentFamily = DB::transaction(function () use ($actor, $currentFamily, $target): bool {
            [$lockedActor, $lockedTarget] = $this->userRowLocks->pair($actor, $target);
            $lockedFamily = Family::query()
                ->whereKey($currentFamily->getKey())
                ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', $lockedActor->id))
                ->whereKey($lockedActor->current_family_id)
                ->lockForUpdate()
                ->firstOrFail();
            $membership = FamilyMembership::query()
                ->whereBelongsTo($lockedFamily, 'family')
                ->whereBelongsTo($lockedTarget, 'user')
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedFamily->memberships()->count() <= 1) {
                throw ValidationException::withMessages([
                    'membership' => __('The final Family Membership cannot be removed. Delete the Family instead.'),
                ]);
            }

            $removedCurrentFamily = $lockedTarget->current_family_id === $lockedFamily->id;

            if ($removedCurrentFamily) {
                $lockedTarget->forceFill(['current_family_id' => null])->save();
            }

            $membership->delete();

            return $removedCurrentFamily;
        }, 3);

        if ($removedCurrentFamily) {
            $freshTarget = $target->fresh();

            if ($freshTarget instanceof User) {
                $this->currentFamily->resolve($freshTarget);
            }
        }
    }
}
