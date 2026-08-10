<?php

declare(strict_types=1);

namespace Tests\Unit\Cookbook;

use App\Cookbook\Values\MetricQuantityInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MetricQuantityInputTest extends TestCase
{
    /** @return iterable<string, array{string, string, string|null, string|null}> */
    public static function canonicalMetricQuantities(): iterable
    {
        yield 'milligrams to grams' => ['500000', 'mg', '500', null];
        yield 'kilograms to grams' => ['1.234567', 'kg', '1234.567', null];
        yield 'centilitres to millilitres' => ['25', 'cl', null, '250'];
        yield 'litres to millilitres' => ['0.5', 'l', null, '500'];
    }

    #[DataProvider('canonicalMetricQuantities')]
    public function test_explicit_metric_units_normalize_exactly(
        string $quantity,
        string $unit,
        ?string $weightGrams,
        ?string $volumeMillilitres,
    ): void {
        $quantities = MetricQuantityInput::packageQuantities($quantity, $unit, '2.5');

        self::assertSame($weightGrams, $quantities->weightGrams);
        self::assertSame($volumeMillilitres, $quantities->volumeMillilitres);
        self::assertSame('2.5', $quantities->pieceCount);
    }

    public function test_conversion_rejects_values_that_do_not_fit_the_canonical_decimal(): void
    {
        self::assertFalse(MetricQuantityInput::isRepresentable('0.000001', 'mg'));
        self::assertFalse(MetricQuantityInput::isRepresentable('99999999999999', 'kg'));
    }
}
