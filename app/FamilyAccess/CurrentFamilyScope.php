<?php

declare(strict_types=1);

namespace App\FamilyAccess;

use App\FamilyAccess\Models\Family;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

final readonly class CurrentFamilyScope
{
    public function __construct(private CurrentFamily $currentFamily) {}

    /**
     * @template TResult
     *
     * @param  Closure(AuthorizedFamilyContext): TResult  $operation
     * @param  positive-int  $attempts
     * @return TResult
     */
    public function withinContext(User $user, Closure $operation, int $attempts = 3): mixed
    {
        return DB::transaction(function () use ($user, $operation): mixed {
            $family = $this->currentFamily->resolve($user) ?? abort(404);

            return $operation(new AuthorizedFamilyContext($user, $family));
        }, $attempts);
    }

    /**
     * @template TResult
     *
     * @param  Closure(Family): TResult  $operation
     * @param  positive-int  $attempts
     * @return TResult
     */
    public function within(User $user, Closure $operation, int $attempts = 3): mixed
    {
        return $this->withinContext(
            $user,
            fn (AuthorizedFamilyContext $context): mixed => $operation($context->family),
            $attempts,
        );
    }
}
