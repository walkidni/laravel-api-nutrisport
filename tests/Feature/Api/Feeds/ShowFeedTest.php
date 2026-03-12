<?php

namespace Tests\Feature\Api\Feeds;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShowFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_json_feed_for_the_resolved_site(): void
    {
        $siteDomain = $this->seedFeedProductForSite('fr');

        $this->getJson("http://{$siteDomain}/v1/feeds/json")
            ->assertOk()
            ->assertExactJson([
                'products' => [
                    [
                        'id' => 1,
                        'name' => 'Whey Protein',
                        'in_stock' => true,
                    ],
                ],
            ]);
    }

    private function seedFeedProductForSite(string $siteCode): string
    {
        $siteDomain = (string) config("sites.domains.{$siteCode}");

        $siteId = DB::table('sites')->insertGetId([
            Site::CODE => $siteCode,
            Site::DOMAIN => $siteDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            Product::NAME => 'Whey Protein',
            Product::STOCK => 10,
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

        return $siteDomain;
    }
}
