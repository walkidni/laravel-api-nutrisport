<?php

namespace App\Http\Resources\Api\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\Services\MoneyFormatterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, bool|int|string>
     */
    public function toArray(Request $request): array
    {
        $moneyFormatter = app(MoneyFormatterService::class);

        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->getAttribute(Product::NAME),
            'price_amount' => $moneyFormatter->formatCents(
                (int) $this->resource->getAttribute(ProductSitePrice::PRICE_AMOUNT_CENTS),
            ),
            'in_stock' => $this->resource->getAttribute(Product::STOCK) > 0,
        ];
    }
}
