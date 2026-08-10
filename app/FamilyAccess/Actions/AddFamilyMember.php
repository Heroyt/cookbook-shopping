<?php

declare(strict_types=1);

namespace App\FamilyAccess\Actions;

use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\FamilyAccess\UserRowLocks;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddFamilyMember
{
    public function __construct(private readonly UserRowLocks $userRowLocks) {}

    public function handle(User $actor, Family $currentFamily, string $email): FamilyMembership
    {
        $target = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ( ! $target instanceof User) {
            throw ValidationException::withMessages([
                'email' => __('No registered User is available for that email.'),
            ]);
        }

        return DB::transaction(function () use ($actor, $currentFamily, $target): FamilyMembership {
            [$lockedActor, $lockedTarget] = $this->userRowLocks->pair($actor, $target);
            $lockedFamily = Family::query()
                ->whereKey($currentFamily->getKey())
                ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', $lockedActor->id))
                ->whereKey($lockedActor->current_family_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedFamily->memberships()->whereBelongsTo($lockedTarget, 'user')->exists()) {
                throw ValidationException::withMessages([
                    'email' => __('That User already belongs to the Current Family.'),
                ]);
            }

            return $lockedFamily->memberships()->create(['user_id' => $lockedTarget->id]);
        }, 3);
    }
}
