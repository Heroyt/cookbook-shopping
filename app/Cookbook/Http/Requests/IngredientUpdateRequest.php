<?php

declare(strict_types=1);

namespace App\Cookbook\Http\Requests;

final class IngredientUpdateRequest extends IngredientWriteRequest
{
    public function ingredientId(): int
    {
        return (int) $this->route('ingredient');
    }
}
