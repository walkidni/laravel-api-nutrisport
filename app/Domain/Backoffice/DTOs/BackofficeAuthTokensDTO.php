<?php

namespace App\Domain\Backoffice\DTOs;

class BackofficeAuthTokensDTO
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
    ) {
    }
}
