<?php

namespace Tests\Feature\Api\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShowProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_a_product_for_the_resolved_site(): void
    {
        [$siteDomain, $siteId, $productId] = $this->seedCatalogForSite('fr');

        $this->getJson("http://{$siteDomain}/v1/products/{$productId}")
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'id' => $productId,
                    'name' => 'Whey Protein',
                    'price_amount' => '29.99',
                    'in_stock' => true,
                ],
            ]);
    }

    public function test_returns_not_found_when_the_product_has_no_price_for_the_resolved_site(): void
    {
        [, , $productId] = $this->seedCatalogForSite('fr');
        $beDomain = (string) config('sites.domains.be');

        DB::table('sites')->insert([
            Site::CODE => 'be',
            Site::DOMAIN => $beDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson("http://{$beDomain}/v1/products/{$productId}")
            ->assertNotFound();
    }

    /**
     * @return array{string, int, int}
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
            ProductSitePrice::PRICE_AMOUNT_CENTS => 2999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$siteDomain, $siteId, $productId];
    }
}
