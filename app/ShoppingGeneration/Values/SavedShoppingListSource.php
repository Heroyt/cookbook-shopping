<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

enum SavedShoppingListSource: string
{
    case SimplePlan = 'simple_plan';
    case Calendar = 'calendar';

    public function label(): string
    {
        return match ($this) {
            self::SimplePlan => __('Simple Plan'),
            self::Calendar => __('Calendar'),
        };
    }
}
