<?php

namespace App\Http\Resources\Api\Backoffice;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\Services\MoneyFormatterService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackofficeProductResource extends JsonResource
{
    public function __construct(Product $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *   id:int,
     *   name:string,
     *   stock:int,
     *   site_prices: array<int, array{site_code:string, price_amount:string}>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Product $product */
        $product = $this->resource;
        $moneyFormatter = app(MoneyFormatterService::class);

        return [
            'id' => (int) $product->getKey(),
            Product::NAME => (string) $product->getAttribute(Product::NAME),
            Product::STOCK => (int) $product->getAttribute(Product::STOCK),
            'site_prices' => $product->sitePrices
                ->sortBy(fn (ProductSitePrice $sitePrice): string => (string) $sitePrice->site?->getAttribute(Site::CODE))
                ->values()
                ->map(fn (ProductSitePrice $sitePrice): array => [
                    'site_code' => (string) $sitePrice->site?->getAttribute(Site::CODE),
                    'price_amount' => $moneyFormatter->formatCents(
                        (int) $sitePrice->getAttribute(ProductSitePrice::PRICE_AMOUNT_CENTS),
                    ),
                ])
                ->all(),
        ];
    }
}
