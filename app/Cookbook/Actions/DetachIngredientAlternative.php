<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Support\Facades\DB;

final readonly class DetachIngredientAlternative
{
    public function handle(AuthorizedFamilyContext $context, int $ingredientId, int $alternativeId): void
    {
        $ids = [$ingredientId, $alternativeId];
        sort($ids);
        $count = Ingredient::query()->whereBelongsTo($context->family)->whereKey($ids)->count();

        abort_unless($count === 2, 404);
        $deleted = DB::table('ingredient_alternatives')
            ->where('family_id', $context->family->id)
            ->where('lower_ingredient_id', $ids[0])
            ->where('higher_ingredient_id', $ids[1])
            ->delete();
        abort_unless($deleted === 1, 404);
    }
}
