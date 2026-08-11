<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Recipe;
use App\FamilyAccess\AuthorizedFamilyContext;
use App\FamilyAccess\Models\Family;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateRecipe
{
    public function __construct(private WriteRecipeAggregate $writer) {}

    /** @param array{name: string, base_servings: string, source_url: ?string, preparation_minutes: ?int, cooking_minutes: ?int, notes: ?string, ingredients: list<array{ingredient_id: int, quantity: string, quantity_kind: string}>, steps: list<string>, tag_ids: list<int>, nutrition: array{energy_kcal: string, fat_grams: string, protein_grams: string, carbohydrate_grams: string}|null} $data */
    public function handle(AuthorizedFamilyContext $context, array $data): Recipe
    {
        return DB::transaction(function () use ($context, $data): Recipe {
            try {
                $recipe = Recipe::query()->create($this->attributes($context->family, $data));
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['name' => __('A Recipe with this name already exists in the Current Family.')]);
            }
            $this->writer->replace($context->family, $recipe, $data);

            return $recipe;
        });
    }

    /**
     * @param  array{name: string, base_servings: string, source_url: ?string, preparation_minutes: ?int, cooking_minutes: ?int, notes: ?string, ingredients: list<array{ingredient_id: int, quantity: string, quantity_kind: string}>, steps: list<string>, tag_ids: list<int>, nutrition: array{energy_kcal: string, fat_grams: string, protein_grams: string, carbohydrate_grams: string}|null}  $data
     * @return array<string, mixed>
     */
    private function attributes(Family $family, array $data): array
    {
        return [
            'family_id' => $family->id, 'name' => $data['name'], 'base_servings' => $data['base_servings'],
            'source_url' => $data['source_url'], 'preparation_minutes' => $data['preparation_minutes'],
            'cooking_minutes' => $data['cooking_minutes'], 'notes' => $data['notes'],
            'nutrition_energy_kcal' => $data['nutrition']['energy_kcal'] ?? null,
            'nutrition_fat_grams' => $data['nutrition']['fat_grams'] ?? null,
            'nutrition_protein_grams' => $data['nutrition']['protein_grams'] ?? null,
            'nutrition_carbohydrate_grams' => $data['nutrition']['carbohydrate_grams'] ?? null,
        ];
    }
}
