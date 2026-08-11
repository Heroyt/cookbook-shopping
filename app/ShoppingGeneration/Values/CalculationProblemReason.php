<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

enum CalculationProblemReason: string
{
    case NonPositiveRequestedServings = 'non_positive_requested_servings';
    case InvalidRequestedServings = 'invalid_requested_servings';
    case NonPositiveBaseServings = 'non_positive_base_servings';
    case InvalidBaseServings = 'invalid_base_servings';
    case NonPositiveRecipeQuantity = 'non_positive_recipe_quantity';
    case InvalidRecipeQuantity = 'invalid_recipe_quantity';
    case MissingPackageQuantity = 'missing_package_quantity';
    case InvalidPackageDefinition = 'invalid_package_definition';
}
