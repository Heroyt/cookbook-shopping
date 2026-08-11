<?php

declare(strict_types=1);

namespace App\Cookbook\Actions;

use App\Cookbook\Models\Ingredient;
use App\Cookbook\Models\Recipe;
use App\Cookbook\Models\RecipeTag;
use App\FamilyAccess\Models\Family;
use Illuminate\Validation\ValidationException;

final class WriteRecipeAggregate
{
    /** @param array{name: string, base_servings: string, source_url: ?string, preparation_minutes: ?int, cooking_minutes: ?int, notes: ?string, ingredients: list<array{ingredient_id: int, quantity: string, quantity_kind: string}>, steps: list<string>, tag_ids: list<int>, nutrition: array{energy_kcal: string, fat_grams: string, protein_grams: string, carbohydrate_grams: string}|null} $data */
    public function replace(Family $family, Recipe $recipe, array $data): void
    {
        $ingredients = $this->resolveIngredients($family, $data['ingredients']);
        $this->assertTagsAvailable($family, $data['tag_ids']);

        $recipe->ingredients()->delete();
        $recipe->steps()->delete();
        foreach ($data['ingredients'] as $index => $line) {
            $recipe->ingredients()->create([
                'family_id' => $family->id,
                'ingredient_id' => $ingredients[$line['ingredient_id']]->id,
                'position' => $index + 1,
                'quantity' => $line['quantity'],
                'quantity_kind' => $line['quantity_kind'],
            ]);
        }
        foreach ($data['steps'] as $index => $instruction) {
            $recipe->steps()->create([
                'family_id' => $family->id,
                'position' => $index + 1,
                'instruction' => $instruction,
            ]);
        }
        $recipe->tags()->syncWithPivotValues($data['tag_ids'], ['family_id' => $family->id]);
    }

    /**
     * @param  list<array{ingredient_id: int, quantity: string, quantity_kind: string}>  $lines
     * @return array<int, Ingredient>
     */
    private function resolveIngredients(Family $family, array $lines): array
    {
        $ids = array_values(array_unique(array_column($lines, 'ingredient_id')));
        $available = Ingredient::query()->whereBelongsTo($family)->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
        $resolved = [];
        foreach ($lines as $index => $line) {
            $ingredient = $available->get($line['ingredient_id']);
            if ( ! $ingredient instanceof Ingredient || $ingredient->archived_at !== null) {
                throw ValidationException::withMessages(["ingredients.{$index}.ingredient_id" => __('The selected Ingredient is unavailable in the Current Family.')]);
            }
            $supported = match ($line['quantity_kind']) {
                'grams' => $ingredient->weight_grams !== null,
                'millilitres' => $ingredient->volume_millilitres !== null,
                'piece' => $ingredient->piece_count !== null,
                default => false,
            };
            if ( ! $supported) {
                throw ValidationException::withMessages(["ingredients.{$index}.quantity_kind" => __('The selected quantity kind is unavailable for this Ingredient package.')]);
            }
            $resolved[$ingredient->id] = $ingredient;
        }

        return $resolved;
    }

    /** @param list<int> $tagIds */
    private function assertTagsAvailable(Family $family, array $tagIds): void
    {
        if ($tagIds === []) {
            return;
        }
        $count = RecipeTag::query()->where('family_id', $family->id)->whereIn('id', $tagIds)->lockForUpdate()->count();
        if ($count !== count($tagIds)) {
            throw ValidationException::withMessages(['tag_ids' => __('One or more selected Recipe Tags are unavailable in the Current Family.')]);
        }
    }
}
