<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

use InvalidArgumentException;

final class MetricQuantityInput
{
    /** @var list<string> */
    public const UNITS = ['mg', 'g', 'kg', 'ml', 'cl', 'l'];

    public static function packageQuantities(?string $metricQuantity, ?string $metricUnit, ?string $pieceCount): IngredientPackageQuantities
    {
        if ($metricQuantity === null || $metricUnit === null) {
            return new IngredientPackageQuantities(null, null, $pieceCount);
        }

        $canonicalValue = self::canonicalValue($metricQuantity, $metricUnit);

        if (in_array($metricUnit, ['mg', 'g', 'kg'], true)) {
            return new IngredientPackageQuantities($canonicalValue, null, $pieceCount);
        }

        return new IngredientPackageQuantities(null, $canonicalValue, $pieceCount);
    }

    public static function isRepresentable(string $quantity, string $unit): bool
    {
        try {
            self::canonicalValue($quantity, $unit);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private static function canonicalValue(string $quantity, string $unit): string
    {
        $shift = match ($unit) {
            'mg' => -3,
            'g', 'ml' => 0,
            'cl' => 1,
            'kg', 'l' => 3,
            default => throw new InvalidArgumentException('Unsupported metric unit.'),
        };

        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '');
        $digits = $whole . $fraction;
        $decimalPosition = strlen($whole) + $shift;

        if ($decimalPosition <= 0) {
            $whole = '0';
            $fraction = str_repeat('0', -$decimalPosition) . $digits;
        } elseif ($decimalPosition >= strlen($digits)) {
            $whole = $digits . str_repeat('0', $decimalPosition - strlen($digits));
            $fraction = '';
        } else {
            $whole = substr($digits, 0, $decimalPosition);
            $fraction = substr($digits, $decimalPosition);
        }

        $whole = ltrim($whole, '0') ?: '0';
        $fraction = rtrim($fraction, '0');

        if (strlen($whole) > 14 || strlen($fraction) > 6 || ($whole === '0' && $fraction === '')) {
            throw new InvalidArgumentException('Normalized metric quantity is outside DECIMAL(20,6).');
        }

        return $whole . ($fraction === '' ? '' : '.' . $fraction);
    }
}
