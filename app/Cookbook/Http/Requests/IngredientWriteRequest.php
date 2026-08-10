<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\IngredientPackageQuantities;
use App\Cookbook\Values\MetricQuantityInput;
use App\Cookbook\Values\NormalizedName;
use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class IngredientWriteRequest extends AuthenticatedRequest
{
    public function ingredientName(): string
    {
        return $this->string('name')->toString();
    }

    public function description(): ?string
    {
        $description = $this->validated('description');

        return is_string($description) ? $description : null;
    }

    public function packageQuantities(): IngredientPackageQuantities
    {
        $metricQuantity = $this->validated('metric_quantity');
        $metricUnit = $this->validated('metric_unit');
        $pieceCount = $this->validated('piece_count');

        return MetricQuantityInput::packageQuantities(
            is_string($metricQuantity) ? $metricQuantity : null,
            is_string($metricUnit) ? $metricUnit : null,
            is_string($pieceCount) ? $pieceCount : null,
        );
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'metric_quantity' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/', 'gt:0'],
            'metric_unit' => ['nullable', 'required_with:metric_quantity', Rule::in(MetricQuantityInput::UNITS)],
            'piece_count' => ['nullable', 'regex:/^\d{1,14}(?:\.\d{1,6})?$/', 'gt:0'],
            'description' => ['nullable', 'string'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'store_section_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'metric_quantity.regex' => __('The metric package quantity must be a positive decimal with at most six fractional places.'),
            'metric_unit.required_with' => __('Select the unit for the metric package quantity.'),
            'piece_count.regex' => __('The package piece count must be a positive decimal with at most six fractional places.'),
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $metricQuantity = $this->input('metric_quantity');
            $metricUnit = $this->input('metric_unit');
            $pieceCount = $this->input('piece_count');

            if ($this->isMissing($metricQuantity) && $this->isMissing($pieceCount)) {
                $validator->errors()->add('quantities', __('Enter a metric package quantity or piece count.'));
            }

            if ( ! $this->isMissing($metricUnit) && $this->isMissing($metricQuantity)) {
                $validator->errors()->add('metric_quantity', __('Enter the metric package quantity.'));
            }

            if (is_string($metricQuantity) && is_string($metricUnit)
                && in_array($metricUnit, MetricQuantityInput::UNITS, true)
                && ! MetricQuantityInput::isRepresentable($metricQuantity, $metricUnit)) {
                $validator->errors()->add('metric_quantity', __('The normalized package quantity must fit a decimal with at most six fractional places.'));
            }
        }];
    }

    public function storeId(): ?int
    {
        $storeId = $this->validated('store_id');

        return is_numeric($storeId) ? (int) $storeId : null;
    }

    public function storeSectionId(): ?int
    {
        $storeSectionId = $this->validated('store_section_id');

        return is_numeric($storeSectionId) ? (int) $storeSectionId : null;
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge(['name' => NormalizedName::from($name)->display]);
        }

        $description = $this->input('description');

        if (is_string($description)) {
            $this->merge(['description' => trim($description) === '' ? null : trim($description)]);
        }
    }

    private function isMissing(mixed $value): bool
    {
        return $value === null || $value === '';
    }
}
