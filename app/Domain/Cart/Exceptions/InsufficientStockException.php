<?php

namespace App\Domain\Cart\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forRequestedQuantity(): self
    {
        return new self('Requested quantity exceeds available stock.');
    }
}
