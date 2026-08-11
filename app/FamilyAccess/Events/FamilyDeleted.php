<?php

declare(strict_types=1);

namespace App\FamilyAccess\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class FamilyDeleted
{
    use Dispatchable;

    public function __construct(public int $familyId) {}
}
