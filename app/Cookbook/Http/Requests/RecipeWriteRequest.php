<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\NormalizedName;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class RecipeWriteRequest extends AuthenticatedRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'base_servings' => ['required', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/', 'gt:0'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'preparation_minutes' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'cooking_minutes' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'notes' => ['nullable', 'string'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*.ingredient_id' => ['required', 'integer', 'min:1'],
            'ingredients.*.quantity' => ['required', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/', 'gt:0'],
            'ingredients.*.quantity_kind' => ['required', Rule::in(['grams', 'millilitres', 'piece'])],
            'steps' => ['sometimes', 'array'],
            'steps.*' => ['nullable', 'string', 'max:10000'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'min:1', 'distinct:strict'],
            'nutrition_energy_kcal' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/'],
            'nutrition_fat_grams' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/'],
            'nutrition_protein_grams' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/'],
            'nutrition_carbohydrate_grams' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/'],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $values = array_map(fn (string $field): mixed => $this->input($field), [
                'nutrition_energy_kcal', 'nutrition_fat_grams', 'nutrition_protein_grams', 'nutrition_carbohydrate_grams',
            ]);
            $hasAny = collect($values)->contains(fn (mixed $value): bool => $value !== null && $value !== '');
            $hasMissing = collect($values)->contains(fn (mixed $value): bool => $value === null || $value === '');
            if ($hasAny && $hasMissing) {
                $validator->errors()->add('nutrition', __('Complete every Recipe Nutrition override field or leave all of them empty.'));
            }
        }];
    }

    /** @return array{name: string, base_servings: string, source_url: ?string, preparation_minutes: ?int, cooking_minutes: ?int, notes: ?string, ingredients: list<array{ingredient_id: int, quantity: string, quantity_kind: string}>, steps: list<string>, tag_ids: list<int>, nutrition: array{energy_kcal: string, fat_grams: string, protein_grams: string, carbohydrate_grams: string}|null} */
    public function recipeData(): array
    {
        $nutritionEnergy = $this->validated('nutrition_energy_kcal');

        return [
            'name' => $this->string('name')->toString(),
            'base_servings' => $this->validatedString('base_servings'),
            'source_url' => $this->nullableString('source_url'),
            'preparation_minutes' => $this->nullableInt('preparation_minutes'),
            'cooking_minutes' => $this->nullableInt('cooking_minutes'),
            'notes' => $this->nullableString('notes'),
            'ingredients' => $this->ingredientsData(),
            'steps' => $this->stepsData(),
            'tag_ids' => $this->tagIds(),
            'nutrition' => is_string($nutritionEnergy) ? [
                'energy_kcal' => $nutritionEnergy,
                'fat_grams' => $this->validatedString('nutrition_fat_grams'),
                'protein_grams' => $this->validatedString('nutrition_protein_grams'),
                'carbohydrate_grams' => $this->validatedString('nutrition_carbohydrate_grams'),
            ] : null,
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $this->merge(['name' => NormalizedName::from($name)->display]);
        }
        foreach (['source_url', 'notes'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $this->merge([$field => trim($value) === '' ? null : trim($value)]);
            }
        }
        $steps = $this->input('steps');
        if (is_array($steps)) {
            $this->merge(['steps' => array_map(static fn (mixed $step): mixed => is_string($step) && trim($step) !== '' ? trim($step) : null, $steps)]);
        }
    }

    private function validatedString(string $key): string
    {
        $value = $this->validated($key);

        return is_string($value) ? $value : '';
    }

    private function nullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) ? $value : null;
    }

    private function nullableInt(string $key): ?int
    {
        $value = $this->validated($key);

        return is_numeric($value) ? (int) $value : null;
    }

    /** @return list<array{ingredient_id: int, quantity: string, quantity_kind: string}> */
    private function ingredientsData(): array
    {
        $validated = $this->validated('ingredients');
        $result = [];
        if ( ! is_array($validated)) {
            return $result;
        }

        foreach ($validated as $line) {
            if ( ! is_array($line)) {
                continue;
            }
            $ingredientId = $line['ingredient_id'] ?? null;
            $quantity = $line['quantity'] ?? null;
            $quantityKind = $line['quantity_kind'] ?? null;
            if (is_numeric($ingredientId) && is_string($quantity) && is_string($quantityKind)) {
                $result[] = ['ingredient_id' => (int) $ingredientId, 'quantity' => $quantity, 'quantity_kind' => $quantityKind];
            }
        }

        return $result;
    }

    /** @return list<string> */
    private function stepsData(): array
    {
        $validated = $this->validated('steps');

        return is_array($validated) ? array_values(array_filter($validated, 'is_string')) : [];
    }

    /** @return list<int> */
    private function tagIds(): array
    {
        $validated = $this->validated('tag_ids');
        $result = [];
        if ( ! is_array($validated)) {
            return $result;
        }
        foreach ($validated as $id) {
            if (is_numeric($id)) {
                $result[] = (int) $id;
            }
        }

        return $result;
    }
}
