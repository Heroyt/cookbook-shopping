<?php

declare(strict_types=1);

namespace Tests\Unit\Cookbook;

use App\Cookbook\Values\NormalizedName;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class NormalizedNameTest extends TestCase
{
    #[TestWith(['pečivo', 2, 'čerstvá zelenina', 1, -1])]
    #[TestWith(['čerstvá zelenina', 1, 'pečivo', 2, 1])]
    #[TestWith(['stejný klíč', 1, 'stejný klíč', 2, -1])]
    #[TestWith(['stejný klíč', 2, 'stejný klíč', 1, 1])]
    #[TestWith(['stejný klíč', 1, 'stejný klíč', 1, 0])]
    public function test_it_compares_normalized_utf_8_bytes_then_stable_identity(
        string $leftKey,
        int $leftId,
        string $rightKey,
        int $rightId,
        int $expectedDirection,
    ): void {
        $actual = NormalizedName::compare($leftKey, $leftId, $rightKey, $rightId);

        self::assertSame($expectedDirection, $actual <=> 0);
    }
}
