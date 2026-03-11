<?php

namespace Tests\Support;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;

final class TestDataHelper
{
    /**
     * @return array{int, string}
     */
    public static function seedSite(string $siteCode): array
    {
        $siteDomain = (string) config("sites.domains.{$siteCode}");

        $site = Site::factory()->create([
            Site::CODE => $siteCode,
            Site::DOMAIN => $siteDomain,
        ]);

        return [(int) $site->getKey(), $siteDomain];
    }

    /**
     * @return array{string, int}
     */
    public static function seedSingleProductCatalogForSite(string $siteCode, int $stock = 10): array
    {
        [$siteId, $siteDomain] = self::seedSite($siteCode);

        $product = Product::factory()->create([
            Product::NAME => 'Whey Protein',
            Product::STOCK => $stock,
        ]);

        ProductSitePrice::factory()->create([
            ProductSitePrice::PRODUCT_ID => (int) $product->getKey(),
            ProductSitePrice::SITE_ID => $siteId,
            ProductSitePrice::PRICE_AMOUNT_CENTS => 2999,
        ]);

        return [$siteDomain, (int) $product->getKey()];
    }
}
