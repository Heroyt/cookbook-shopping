<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

use App\Http\Requests\AuthenticatedRequest;
use Illuminate\Contracts\Validation\ValidationRule;

final class IngredientAlternativeStoreRequest extends AuthenticatedRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return ['alternative_id' => ['required', 'integer', 'min:1']];
    }

    public function alternativeId(): int
    {
        return $this->integer('alternative_id');
    }
}
