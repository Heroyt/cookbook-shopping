<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\IngredientPackageQuantities;
use App\Cookbook\Values\NormalizedName;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Validator;

final class IngredientStoreRequest extends AuthenticatedRequest
{
    public function ingredientName(): string
    {
        return $this->string('name')->toString();
    }

    public function packageQuantities(): IngredientPackageQuantities
    {
        return new IngredientPackageQuantities(
            $this->validatedNullableString('weight_grams'),
            $this->validatedNullableString('volume_millilitres'),
            $this->validatedNullableString('piece_count'),
        );
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'weight_grams' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/', 'gt:0'],
            'volume_millilitres' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/', 'gt:0'],
            'piece_count' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/', 'gt:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'weight_grams.regex' => __('The package weight must be a positive decimal with at most six fractional places.'),
            'volume_millilitres.regex' => __('The package volume must be a positive decimal with at most six fractional places.'),
            'piece_count.regex' => __('The package piece count must be a positive decimal with at most six fractional places.'),
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $weight = $this->input('weight_grams');
            $volume = $this->input('volume_millilitres');
            $pieces = $this->input('piece_count');

            if ($this->isMissingQuantity($weight) && $this->isMissingQuantity($volume) && $this->isMissingQuantity($pieces)) {
                $validator->errors()->add('quantities', __('Enter a package weight, volume, or piece count.'));
            }

            if ( ! $this->isMissingQuantity($weight) && ! $this->isMissingQuantity($volume)) {
                $message = __('Package weight and volume cannot be entered together.');
                $validator->errors()->add('weight_grams', $message);
                $validator->errors()->add('volume_millilitres', $message);
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if ( ! is_string($name)) {
            return;
        }

        $this->merge(['name' => NormalizedName::from($name)->display]);
    }

    private function validatedNullableString(string $key): ?string
    {
        $value = $this->validated($key);

        return is_string($value) ? $value : null;
    }

    private function isMissingQuantity(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
