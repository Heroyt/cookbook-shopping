<?php

declare(strict_types=1);

namespace Tests\Unit\Cookbook;

use App\Cookbook\Values\IngredientPackageQuantities;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class IngredientPackageQuantitiesTest extends TestCase
{
    /** @return iterable<string, array{?string, ?string, ?string, list<string>}> */
    public static function displayCases(): iterable
    {
        yield 'rounding below threshold' => ['999.125000', null, null, ['999,13 g']];
        yield 'derived large volume and fractional pieces' => [null, '1500.000000', '2.500000', ['1,5 l', '2,5 ks']];
        yield 'rounding carry without floating point' => ['99999999999999.999999', null, null, ['100000000000 kg']];
    }

    /**
     * @param  list<string>  $expected
     */
    #[DataProvider('displayCases')]
    public function test_it_formats_canonical_quantities_exactly(
        ?string $weightGrams,
        ?string $volumeMillilitres,
        ?string $pieceCount,
        array $expected,
    ): void {
        $quantities = new IngredientPackageQuantities($weightGrams, $volumeMillilitres, $pieceCount);

        $this->assertSame($expected, $quantities->display());
    }
}
