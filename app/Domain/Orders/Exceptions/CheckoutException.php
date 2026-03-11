<?php

namespace App\Domain\Orders\Exceptions;

use RuntimeException;

class CheckoutException extends RuntimeException
{
    public static function emptyCart(): self
    {
        return new self('Checkout requires a non-empty cart.');
    }

    public static function unavailableCartLine(): self
    {
        return new self('One or more cart items are no longer available for checkout.');
    }

    public static function stockMismatch(): self
    {
        return new self('Requested quantity exceeds available stock.');
    }
}
