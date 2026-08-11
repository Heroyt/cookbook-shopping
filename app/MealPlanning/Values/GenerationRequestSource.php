<?php

declare(strict_types=1);

namespace App\MealPlanning\Values;

enum GenerationRequestSource
{
    case SimplePlan;
    case Calendar;

    public function includesArchivedRecipes(): bool
    {
        return $this === self::Calendar;
    }

    public function validationField(): string
    {
        return match ($this) {
            self::SimplePlan => 'plan',
            self::Calendar => 'dates',
        };
    }

    public function unavailableRecipeMessage(): string
    {
        return match ($this) {
            self::SimplePlan => __('The Simple Plan contains a Recipe that is no longer available in the Current Family.'),
            self::Calendar => __('The Calendar selection contains a Recipe that is no longer available in the Current Family.'),
        };
    }

    public function recipeWithoutIngredientsMessage(): string
    {
        return match ($this) {
            self::SimplePlan => __('The Simple Plan contains a Recipe without Ingredients. Update the Recipe and try again.'),
            self::Calendar => __('The Calendar selection contains a Recipe without Ingredients. Update the Recipe and try again.'),
        };
    }

    public function unavailableIngredientMessage(): string
    {
        return match ($this) {
            self::SimplePlan => __('The Simple Plan contains an unavailable Ingredient. Update the Recipe and try again.'),
            self::Calendar => __('The Calendar selection contains an unavailable Ingredient. Update the Recipe and try again.'),
        };
    }
}
