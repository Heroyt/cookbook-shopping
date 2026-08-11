<?php

declare(strict_types=1);

namespace App\ShoppingGeneration;

final class NormalizedNameComparator
{
    public static function compare(
        string $leftName,
        int $leftId,
        string $rightName,
        int $rightId,
    ): int {
        $nameComparison = strcmp($leftName, $rightName);

        return $nameComparison !== 0 ? $nameComparison : $leftId <=> $rightId;
    }
}
