<?php

namespace Tests\Feature\Api\Cart;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ShowCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_an_empty_cart_when_no_cart_exists(): void
    {
        $siteDomain = (string) config('sites.domains.fr');

        DB::table('sites')->insert([
            Site::CODE => 'fr',
            Site::DOMAIN => $siteDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson("http://{$siteDomain}/v1/cart")
            ->assertOk()
            ->assertHeaderMissing('X-Cart-Token')
            ->assertExactJson([
                'data' => [
                    'lines' => [],
                    'item_count' => 0,
                    'total_amount' => 0,
                ],
            ]);
    }

    public function test_returns_the_cached_cart_when_a_valid_token_exists(): void
    {
        $siteCode = 'fr';
        $siteDomain = (string) config("sites.domains.{$siteCode}");
        $tokenHeader = (string) config('cart.token_header');
        $token = 'cart-token-123';

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

        Cache::put("cart:{$siteCode}:{$token}", [
            'lines' => [
                [
                    'product_id' => $productId,
                    'quantity' => 2,
                ],
            ],
        ], (int) config('cart.ttl_seconds'));

        $this->withHeader($tokenHeader, $token)
            ->getJson("http://{$siteDomain}/v1/cart")
            ->assertOk()
            ->assertHeader($tokenHeader, $token)
            ->assertExactJson([
                'data' => [
                    'lines' => [
                        [
                            'product_id' => $productId,
                            'name' => 'Whey Protein',
                            'quantity' => 2,
                            'unit_price_amount' => 2999,
                            'line_total_amount' => 5998,
                        ],
                    ],
                    'item_count' => 1,
                    'total_amount' => 5998,
                ],
            ]);
    }
}
