<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AttachIngredientAlternative
{
    public function handle(AuthorizedFamilyContext $context, int $ingredientId, int $alternativeId): void
    {
        if ($ingredientId === $alternativeId) {
            $this->invalid();
        }

        $ids = [$ingredientId, $alternativeId];
        sort($ids);
        $ingredients = Ingredient::query()
            ->whereBelongsTo($context->family)
            ->whereNull('archived_at')
            ->whereKey($ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($ingredients->count() !== 2) {
            $this->invalid();
        }

        $inserted = DB::table('ingredient_alternatives')->insertOrIgnore([
            'family_id' => $context->family->id,
            'lower_ingredient_id' => $ids[0],
            'higher_ingredient_id' => $ids[1],
        ]);

        if ($inserted !== 1) {
            throw ValidationException::withMessages([
                'alternative_id' => __('These Ingredients are already linked as Alternatives.'),
            ]);
        }
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages([
            'alternative_id' => __('Select another active Ingredient from the Current Family.'),
        ]);
    }
}
