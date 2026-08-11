<?php

declare(strict_types=1);

namespace App\ShoppingGeneration\Values;

enum QuantityKind: string
{
    case Grams = 'grams';
    case Millilitres = 'millilitres';
    case Piece = 'piece';
}
