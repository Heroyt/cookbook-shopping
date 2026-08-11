<?php

declare(strict_types=1);

namespace App\FamilyAccess\Events;

use Closure;
use Illuminate\Foundation\Events\Dispatchable;

final class FamilyDeleting
{
    use Dispatchable;

    /** @var list<Closure(): void> */
    private array $rollbackCallbacks = [];

    public function __construct(public readonly int $familyId) {}

    /** @param Closure(): void $callback */
    public function onRollback(Closure $callback): void
    {
        $this->rollbackCallbacks[] = $callback;
    }

    public function rollback(): void
    {
        foreach (array_reverse($this->rollbackCallbacks) as $callback) {
            $callback();
        }
    }
}
