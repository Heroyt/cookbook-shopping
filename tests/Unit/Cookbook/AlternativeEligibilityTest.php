<?php

declare(strict_types=1);

namespace Tests\Unit\Cookbook;

use App\Cookbook\Values\AlternativeEligibility;
use App\Cookbook\Values\IngredientPackageQuantities;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AlternativeEligibilityTest extends TestCase
{
    #[Test]
    public function it_requires_every_canonical_quantity_kind_without_cross_kind_conversion(): void
    {
        $package = new IngredientPackageQuantities('500', null, '10');

        self::assertTrue(AlternativeEligibility::allows($package, ['grams']));
        self::assertTrue(AlternativeEligibility::allows($package, ['piece', 'grams']));
        self::assertFalse(AlternativeEligibility::allows($package, ['millilitres']));
        self::assertFalse(AlternativeEligibility::allows($package, ['grams', 'millilitres']));
    }
}
