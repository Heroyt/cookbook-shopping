<?php

declare(strict_types=1);

namespace App\FamilyAccess;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class UserRowLocks
{
    /** @return array{User, User} */
    public function pair(User $first, User $second): array
    {
        $lockedUsers = User::query()
            ->whereKey([$first->getKey(), $second->getKey()])
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $lockedFirst = $lockedUsers->get($first->id);
        $lockedSecond = $lockedUsers->get($second->id);

        if ( ! $lockedFirst instanceof User || ! $lockedSecond instanceof User) {
            throw (new ModelNotFoundException())->setModel(User::class);
        }

        return [$lockedFirst, $lockedSecond];
    }
}
