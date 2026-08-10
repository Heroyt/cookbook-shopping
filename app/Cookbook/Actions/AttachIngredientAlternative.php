<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\FamilyAccess\CurrentFamilyScope;
use App\FamilyAccess\Models\Family;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AttachIngredientAlternative
{
    public function __construct(private CurrentFamilyScope $currentFamilyScope) {}

    public function handle(User $user, int $ingredientId, int $alternativeId): void
    {
        $this->currentFamilyScope->within($user, function (Family $family) use ($ingredientId, $alternativeId): void {
            if ($ingredientId === $alternativeId) {
                $this->invalid();
            }

            $ids = [$ingredientId, $alternativeId];
            sort($ids);
            $ingredients = Ingredient::query()
                ->whereBelongsTo($family)
                ->whereNull('archived_at')
                ->whereKey($ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($ingredients->count() !== 2) {
                $this->invalid();
            }

            $inserted = DB::table('ingredient_alternatives')->insertOrIgnore([
                'family_id' => $family->id,
                'lower_ingredient_id' => $ids[0],
                'higher_ingredient_id' => $ids[1],
            ]);

            if ($inserted !== 1) {
                throw ValidationException::withMessages([
                    'alternative_id' => __('These Ingredients are already linked as Alternatives.'),
                ]);
            }
        });
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages([
            'alternative_id' => __('Select another active Ingredient from the Current Family.'),
        ]);
    }
}
