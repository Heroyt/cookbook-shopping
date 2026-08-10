<?php

declare(strict_types=1);

namespace App\FamilyAccess\Actions;

use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreateFamily
{
    public function handle(User $creator, string $name): Family
    {
        return DB::transaction(function () use ($creator, $name): Family {
            $lockedCreator = User::query()
                ->whereKey($creator->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $family = Family::query()->create(['name' => $name]);

            $family->memberships()->create(['user_id' => $lockedCreator->id]);

            return $family;
        });
    }
}
