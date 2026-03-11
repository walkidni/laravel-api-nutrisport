<?php

namespace App\Domain\Cart\DTOs;

final readonly class CartViewDTO
{
    /**
     * @param array<int, array{product_id: int, name: string, quantity: int, unit_price_amount_cents: int, line_total_amount_cents: int}> $lines
     */
    public function __construct(
        public array $lines,
        public int $itemCount,
        public int $totalAmountCents,
        public ?string $token,
    ) {
    }

    public static function empty(?string $token = null): self
    {
        return new self(
            lines: [],
            itemCount: 0,
            totalAmountCents: 0,
            token: $token,
        );
    }
}
