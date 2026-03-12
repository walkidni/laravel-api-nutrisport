<?php

namespace App\Domain\Feeds\Queries;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Feeds\DTOs\FeedProductDTO;
use App\Domain\Shared\SiteContext\Site;

final class ListFeedProductsQuery
{
    /**
     * @return array<int, FeedProductDTO>
     */
    public function __invoke(Site $site): array
    {
        return Product::query()
            ->select([
                'products.id',
                'products.name',
                'products.stock',
            ])
            ->join('product_site_prices', 'product_site_prices.product_id', '=', 'products.id')
            ->where('product_site_prices.site_id', $site->getKey())
            ->orderBy('products.id')
            ->get()
            ->map(fn (Product $product): FeedProductDTO => new FeedProductDTO(
                id: (int) $product->getKey(),
                name: (string) $product->getAttribute(Product::NAME),
                inStock: (int) $product->getAttribute(Product::STOCK) > 0,
            ))
            ->all();
    }
}
