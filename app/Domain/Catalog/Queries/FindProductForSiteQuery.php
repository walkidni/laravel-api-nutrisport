<?php

namespace App\Domain\Catalog\Queries;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;

class FindProductForSiteQuery
{
    public function __invoke(Site $site, int $productId): ?Product
    {
        $product = new Product();
        $price = new ProductSitePrice();
        $productKeyName = $product->getKeyName();

        return Product::query()
            ->select([
                $product->qualifyColumn($productKeyName),
                $product->qualifyColumn(Product::NAME),
                $product->qualifyColumn(Product::STOCK),
                $price->qualifyColumn(ProductSitePrice::PRICE_AMOUNT).' as '.ProductSitePrice::PRICE_AMOUNT,
            ])
            ->join(
                $price->getTable(),
                $product->qualifyColumn($productKeyName),
                '=',
                $price->qualifyColumn(ProductSitePrice::PRODUCT_ID),
            )
            ->where($product->qualifyColumn($productKeyName), $productId)
            ->where($price->qualifyColumn(ProductSitePrice::SITE_ID), $site->getKey())
            ->first();
    }
}
