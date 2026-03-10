<?php

namespace App\Http\Resources\Api\Cart;

use App\Domain\Cart\DTOs\CartViewDTO;
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
     * @return array{lines: array<int, array<string, int|string>>, item_count: int, total_amount: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'lines' => $this->resource->lines,
            'item_count' => $this->resource->itemCount,
            'total_amount' => $this->resource->totalAmount,
        ];
    }
}
