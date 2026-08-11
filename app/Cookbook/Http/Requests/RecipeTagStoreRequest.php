<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Cookbook\Values\NormalizedName;
use App\Http\Requests\AuthenticatedRequest;

final class RecipeTagStoreRequest extends AuthenticatedRequest
{
    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255']];
    }

    public function tagName(): string
    {
        return $this->string('name')->toString();
    }

    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        if (is_string($name)) {
            $this->merge(['name' => NormalizedName::from($name)->display]);
        }
    }
}
