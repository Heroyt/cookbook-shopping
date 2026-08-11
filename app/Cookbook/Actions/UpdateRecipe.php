<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\AuthorizedFamilyContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class UpdateRecipe
{
    public function __construct(private WriteRecipeAggregate $writer) {}

    /** @param array{name: string, base_servings: string, source_url: ?string, preparation_minutes: ?int, cooking_minutes: ?int, notes: ?string, ingredients: list<array{ingredient_id: int, quantity: string, quantity_kind: string}>, steps: list<string>, tag_ids: list<int>, nutrition: array{energy_kcal: string, fat_grams: string, protein_grams: string, carbohydrate_grams: string}|null} $data */
    public function handle(AuthorizedFamilyContext $context, int $recipeId, int $version, array $data): Recipe
    {
        return DB::transaction(function () use ($context, $recipeId, $version, $data): Recipe {
            $recipe = Recipe::query()->whereBelongsTo($context->family)->whereKey($recipeId)->lockForUpdate()->firstOrFail();
            if ($recipe->archived_at !== null) {
                throw ValidationException::withMessages(['recipe' => __('Restore the Recipe before editing it.')]);
            }
            if ($recipe->version !== $version) {
                throw ValidationException::withMessages(['version' => __('The Recipe changed. Review the current version and try again.')]);
            }
            $this->writer->replace($context->family, $recipe, $data);
            $recipe->fill([
                'name' => $data['name'], 'base_servings' => $data['base_servings'], 'version' => $recipe->version + 1,
                'source_url' => $data['source_url'], 'preparation_minutes' => $data['preparation_minutes'],
                'cooking_minutes' => $data['cooking_minutes'], 'notes' => $data['notes'],
                'nutrition_energy_kcal' => $data['nutrition']['energy_kcal'] ?? null,
                'nutrition_fat_grams' => $data['nutrition']['fat_grams'] ?? null,
                'nutrition_protein_grams' => $data['nutrition']['protein_grams'] ?? null,
                'nutrition_carbohydrate_grams' => $data['nutrition']['carbohydrate_grams'] ?? null,
            ]);
            try {
                $recipe->save();
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['name' => __('A Recipe with this name already exists in the Current Family.')]);
            }

            return $recipe;
        });
    }
}
