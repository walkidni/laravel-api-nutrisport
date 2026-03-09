<?php

namespace Tests\Feature\Api\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ListProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_products_for_the_resolved_site(): void
    {
        $siteId = DB::table('sites')->insertGetId([
            Site::CODE => 'fr',
            Site::DOMAIN => 'nutri-sport.fr',
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

        $this->withHeader('Host', 'nutri-sport.fr')
            ->getJson('/api/products')
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
}
