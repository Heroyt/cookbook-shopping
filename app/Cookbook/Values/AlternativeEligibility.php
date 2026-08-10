<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

final class AlternativeEligibility
{
    /** @param list<'grams'|'millilitres'|'piece'> $requiredKinds */
    public static function allows(IngredientPackageQuantities $package, array $requiredKinds): bool
    {
        $availableKinds = array_filter([
            'grams' => $package->weightGrams !== null,
            'millilitres' => $package->volumeMillilitres !== null,
            'piece' => $package->pieceCount !== null,
        ]);

        foreach ($requiredKinds as $requiredKind) {
            if ( ! isset($availableKinds[$requiredKind])) {
                return false;
            }
        }

        return true;
    }
}
