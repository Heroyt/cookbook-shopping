<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

final class RecipeUpdateRequest extends RecipeWriteRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1'], ...parent::rules()];
    }

    public function recipeVersion(): int
    {
        $version = $this->validated('version');

        return is_numeric($version) ? (int) $version : 0;
    }
}
