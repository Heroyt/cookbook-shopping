<?php

declare(strict_types=1);

namespace App\Cookbook\Values;

final readonly class IngredientPackageQuantities
{
    public function __construct(
        public ?string $weightGrams,
        public ?string $volumeMillilitres,
        public ?string $pieceCount,
    ) {}

    /** @return list<string> */
    public function display(): array
    {
        $quantities = [];

        if ($this->weightGrams !== null) {
            $quantities[] = $this->displayMeasurement($this->weightGrams, 'g', 'kg');
        }

        if ($this->volumeMillilitres !== null) {
            $quantities[] = $this->displayMeasurement($this->volumeMillilitres, 'ml', 'l');
        }

        if ($this->pieceCount !== null) {
            $quantities[] = $this->displayNumber(...$this->decimalParts($this->pieceCount)) . ' ks';
        }

        return $quantities;
    }

    private function displayMeasurement(string $canonicalValue, string $smallUnit, string $largeUnit): string
    {
        [$whole, $fraction] = $this->decimalParts($canonicalValue);

        if (strlen($whole) > 3) {
            $fraction = substr($whole, -3) . $fraction;
            $whole = substr($whole, 0, -3);

            return $this->displayNumber($whole, $fraction) . ' ' . $largeUnit;
        }

        return $this->displayNumber($whole, $fraction) . ' ' . $smallUnit;
    }

    private function displayNumber(string $whole, string $fraction = ''): string
    {
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = str_pad($fraction, 3, '0');
        $scaled = ltrim($whole . substr($fraction, 0, 2), '0') ?: '0';

        if ($fraction[2] >= '5') {
            $scaled = $this->incrementDigits($scaled);
        }

        $scaled = str_pad($scaled, 3, '0', STR_PAD_LEFT);
        $displayWhole = ltrim(substr($scaled, 0, -2), '0') ?: '0';
        $displayFraction = rtrim(substr($scaled, -2), '0');

        return $displayWhole . ($displayFraction === '' ? '' : ',' . $displayFraction);
    }

    /** @return array{string, string} */
    private function decimalParts(string $value): array
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return [ltrim($whole, '0') ?: '0', $fraction];
    }

    private function incrementDigits(string $digits): string
    {
        for ($position = strlen($digits) - 1; $position >= 0; $position--) {
            if ($digits[$position] !== '9') {
                $digits[$position] = (string) ((int) $digits[$position] + 1);

                return $digits;
            }

            $digits[$position] = '0';
        }

        return '1' . $digits;
    }
}
