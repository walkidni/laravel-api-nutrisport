<?php

namespace App\Http\Resources\Api\Checkout;

use App\Domain\Orders\DTOs\CheckoutResultDTO;
use App\Domain\Shared\Services\MoneyFormatterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResultResource extends JsonResource
{
    public function __construct(CheckoutResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{id: int, reference: string, status: string, payment_method: string, delivery_method: string, delivery_amount: string, total_amount: string, lines: array<int, array{product_id: int, product_name: string, quantity: int, unit_price_amount: string, line_total_amount: string}>}
     */
    public function toArray(Request $request): array
    {
        $moneyFormatter = app(MoneyFormatterService::class);

        return [
            'id' => $this->resource->id,
            'reference' => $this->resource->reference,
            'status' => $this->resource->status,
            'payment_method' => $this->resource->paymentMethod,
            'delivery_method' => $this->resource->deliveryMethod,
            'delivery_amount' => $moneyFormatter->formatCents($this->resource->deliveryAmountCents),
            'total_amount' => $moneyFormatter->formatCents($this->resource->totalAmountCents),
            'lines' => array_map(
                static fn (array $line): array => [
                    'product_id' => $line['product_id'],
                    'product_name' => $line['product_name'],
                    'quantity' => $line['quantity'],
                    'unit_price_amount' => $moneyFormatter->formatCents($line['unit_price_amount_cents']),
                    'line_total_amount' => $moneyFormatter->formatCents($line['line_total_amount_cents']),
                ],
                $this->resource->lines,
            ),
        ];
    }
}
