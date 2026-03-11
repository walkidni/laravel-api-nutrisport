<?php

namespace App\Domain\Customers\DTOs;

class CustomerAuthTokensDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
    ) {
    }
}
