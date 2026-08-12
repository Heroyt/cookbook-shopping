<?php

declare(strict_types=1);

namespace App\Cookbook\Exceptions;

use App\Cookbook\Values\EntityMediaFailure;
use RuntimeException;

final class EntityMediaRejected extends RuntimeException
{
    public function __construct(public readonly EntityMediaFailure $failure)
    {
        parent::__construct($failure->message());
    }
}
