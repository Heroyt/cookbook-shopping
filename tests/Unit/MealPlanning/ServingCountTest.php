<?php

declare(strict_types=1);

namespace Tests\Unit\MealPlanning;

use App\MealPlanning\Values\ServingCount;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ServingCountTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function invalidServingCounts(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-1'];
        yield 'too many decimal places' => ['1.0000001'];
        yield 'too many integer places' => ['100000000000000'];
        yield 'scientific notation' => ['1e2'];
    }

    public function test_it_accumulates_fractional_servings_exactly(): void
    {
        $total = ServingCount::from('1.500000')->plus(ServingCount::from('0.75'));

        self::assertSame('2.25', $total->toString());
    }

    #[DataProvider('invalidServingCounts')]
    public function test_it_rejects_non_canonical_or_non_positive_values(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        ServingCount::from($value);
    }

    public function test_it_rejects_a_total_outside_the_canonical_decimal_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ServingCount::from('99999999999999.999999')->plus(ServingCount::from('0.000001'));
    }
}
