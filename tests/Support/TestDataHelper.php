<?php

namespace Tests\Support;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Support\Facades\DB;

final class TestDataHelper
{
    /**
     * @return array{int, string}
     */
    public static function seedSite(string $siteCode): array
    {
        $siteDomain = (string) config("sites.domains.{$siteCode}");

        $siteId = DB::table('sites')->insertGetId([
            Site::CODE => $siteCode,
            Site::DOMAIN => $siteDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$siteId, $siteDomain];
    }

    /**
     * @return array{string, int}
     */
    public static function seedSingleProductCatalogForSite(string $siteCode, int $stock = 10): array
    {
        [$siteId, $siteDomain] = self::seedSite($siteCode);

        $productId = DB::table('products')->insertGetId([
            Product::NAME => 'Whey Protein',
            Product::STOCK => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_site_prices')->insert([
            ProductSitePrice::PRODUCT_ID => $productId,
            ProductSitePrice::SITE_ID => $siteId,
            ProductSitePrice::PRICE_AMOUNT_CENTS => 2999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$siteDomain, $productId];
    }
}
