<?php

declare(strict_types=1);

namespace App\FamilyAccess;

use App\FamilyAccess\Models\Family;
use App\FamilyAccess\Models\FamilyMembership;
use App\Models\User;

final readonly class AuthorizedFamilyContext
{
    public function __construct(public User $user, public Family $family)
    {
        FamilyMembership::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($family)
            ->firstOrFail();
    }
}
