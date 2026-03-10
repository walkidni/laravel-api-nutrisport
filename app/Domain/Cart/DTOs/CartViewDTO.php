<?php

namespace App\Domain\Cart\DTOs;

final readonly class CartViewDTO
{
    /**
     * @param array<int, array<string, int|string>> $lines
     */
    public function __construct(
        public array $lines,
        public int $itemCount,
        public int $totalAmount,
        public ?string $token,
    ) {
    }

    public static function empty(?string $token = null): self
    {
        return new self(
            lines: [],
            itemCount: 0,
            totalAmount: 0,
            token: $token,
        );
    }
}
