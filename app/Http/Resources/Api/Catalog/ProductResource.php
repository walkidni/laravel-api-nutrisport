<?php

namespace App\Http\Resources\Api\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, bool|int|string>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->getAttribute(Product::NAME),
            ProductSitePrice::PRICE_AMOUNT => $this->resource->getAttribute(ProductSitePrice::PRICE_AMOUNT),
            'in_stock' => $this->resource->getAttribute(Product::STOCK) > 0,
        ];
    }
}
