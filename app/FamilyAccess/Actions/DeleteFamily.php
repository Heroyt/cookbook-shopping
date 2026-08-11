<?php

declare(strict_types=1);

namespace App\FamilyAccess\Actions;

use App\FamilyAccess\CurrentFamily;
use App\FamilyAccess\Events\FamilyDeleted;
use App\FamilyAccess\Events\FamilyDeleting;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

final class DeleteFamily
{
    public function __construct(private readonly CurrentFamily $currentFamily) {}

    public function handle(User $actor, Family $currentFamily, string $confirmedName): void
    {
        $deleting = null;

        try {
            DB::transaction(function () use ($actor, $currentFamily, $confirmedName, &$deleting): void {
                $memberIds = $currentFamily->memberships()
                    ->select('user_id')
                    ->pluck('user_id');
                $lockedUsers = User::query()
                    ->whereKey($memberIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
                $lockedActor = $lockedUsers->get($actor->id);

                if ( ! $lockedActor instanceof User) {
                    abort(404);
                }

                $lockedFamily = Family::query()
                    ->whereKey($currentFamily->getKey())
                    ->whereKey($lockedActor->current_family_id)
                    ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', $lockedActor->id))
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($confirmedName !== $lockedFamily->name) {
                    throw ValidationException::withMessages([
                        'family_name' => __('The Family name does not match.'),
                    ]);
                }

                $lockedFamily->delete();
                $deleting = new FamilyDeleting($lockedFamily->id);
                event($deleting);
            }, 1);
        } catch (Throwable $exception) {
            $deleting?->rollback();

            throw $exception;
        }

        FamilyDeleted::dispatch($currentFamily->id);

        $freshActor = $actor->fresh();

        if ($freshActor instanceof User) {
            $this->currentFamily->resolve($freshActor);
        }
    }
}
