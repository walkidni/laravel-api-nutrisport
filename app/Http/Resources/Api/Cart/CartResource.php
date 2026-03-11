<?php

namespace App\Http\Resources\Api\Cart;

use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Shared\Services\MoneyFormatterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function __construct(CartViewDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @param CartViewDTO $resource
     * @return array{lines: array<int, array{product_id: int, name: string, quantity: int, unit_price_amount: string, line_total_amount: string}>, item_count: int, total_amount: string}
     */
    public function toArray(Request $request): array
    {
        $moneyFormatter = app(MoneyFormatterService::class);

        return [
            'lines' => array_map(
                static fn (array $line): array => [
                    'product_id' => $line['product_id'],
                    'name' => $line['name'],
                    'quantity' => $line['quantity'],
                    'unit_price_amount' => $moneyFormatter->formatCents($line['unit_price_amount_cents']),
                    'line_total_amount' => $moneyFormatter->formatCents($line['line_total_amount_cents']),
                ],
                $this->resource->lines,
            ),
            'item_count' => $this->resource->itemCount,
            'total_amount' => $moneyFormatter->formatCents($this->resource->totalAmountCents),
        ];
    }
}
