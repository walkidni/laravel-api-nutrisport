<?php

namespace App\Domain\Orders\DTOs;

final class CheckoutResultDTO
{
    /**
     * @param array<int, array{product_id: int, product_name: string, quantity: int, unit_price_amount_cents: int, line_total_amount_cents: int}> $lines
     */
    public function __construct(
        public readonly int $id,
        public readonly string $reference,
        public readonly string $status,
        public readonly string $paymentMethod,
        public readonly string $deliveryMethod,
        public readonly int $deliveryAmountCents,
        public readonly int $totalAmountCents,
        public readonly array $lines,
    ) {
    }
}
