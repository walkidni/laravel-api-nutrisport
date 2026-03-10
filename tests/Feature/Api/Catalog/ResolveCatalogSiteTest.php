<?php

namespace Tests\Feature\Api\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResolveCatalogSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_header_fallback_is_disabled_by_default(): void
    {
        $this->assertFalse(config('app.site_header_fallback_enabled'));
    }

    public function test_allows_resolving_the_site_from_the_header_when_enabled(): void
    {
        [$siteId, $productId] = $this->seedCatalogForSite('be');

        config()->set('app.site_header_fallback_enabled', true);

        $this->withHeader('X-Site-Code', 'be')
            ->getJson('http://unknown.api.nutri-core.com/v1/products')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $productId,
                        'name' => 'Whey Protein',
                        'price_amount' => 2999,
                        'in_stock' => true,
                    ],
                ],
            ]);
    }

    public function test_rejects_the_site_header_when_fallback_is_disabled(): void
    {
        $this->seedCatalogForSite('be');

        config()->set('app.site_header_fallback_enabled', false);

        $this->withHeader('X-Site-Code', 'be')
            ->getJson('http://unknown.api.nutri-core.com/v1/products')
            ->assertNotFound();
    }

    /**
     * @return array{int, int}
     */
    private function seedCatalogForSite(string $siteCode): array
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
            ProductSitePrice::PRICE_AMOUNT => 2999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$siteId, $productId];
    }
}
