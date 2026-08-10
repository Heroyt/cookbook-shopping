<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DetachIngredientAlternative
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, int $ingredientId, int $alternativeId): void
    {
        $this->currentFamilyScope->within($user, function (Family $family) use ($ingredientId, $alternativeId): void {
            $ids = [$ingredientId, $alternativeId];
            sort($ids);
            $count = Ingredient::query()->whereBelongsTo($family)->whereKey($ids)->count();

            abort_unless($count === 2, 404);
            $deleted = DB::table('ingredient_alternatives')
                ->where('family_id', $family->id)
                ->where('lower_ingredient_id', $ids[0])
                ->where('higher_ingredient_id', $ids[1])
                ->delete();
            abort_unless($deleted === 1, 404);
        });
    }
}
