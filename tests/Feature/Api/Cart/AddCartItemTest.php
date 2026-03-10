<?php

namespace Tests\Feature\Api\Cart;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AddCartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_a_product_to_the_cart_and_returns_the_created_cart(): void
    {
        [$siteDomain, $productId] = $this->seedCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');

        $response = $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ]);

        $response
            ->assertOk()
            ->assertHeader($tokenHeader)
            ->assertExactJson([
                'data' => [
                    'lines' => [
                        [
                            'product_id' => $productId,
                            'name' => 'Whey Protein',
                            'quantity' => 1,
                            'unit_price_amount' => 2999,
                            'line_total_amount' => 2999,
                        ],
                    ],
                    'item_count' => 1,
                    'total_amount' => 2999,
                ],
            ]);
    }

    public function test_adding_the_same_product_again_increments_its_quantity(): void
    {
        [$siteDomain, $productId] = $this->seedCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');

        $token = $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ])->headers->get($tokenHeader);

        $this->withHeader($tokenHeader, (string) $token)
            ->postJson("http://{$siteDomain}/v1/cart/items", [
                'product_id' => $productId,
                'quantity' => 2,
            ])
            ->assertOk()
            ->assertHeader($tokenHeader, (string) $token)
            ->assertExactJson([
                'data' => [
                    'lines' => [
                        [
                            'product_id' => $productId,
                            'name' => 'Whey Protein',
                            'quantity' => 3,
                            'unit_price_amount' => 2999,
                            'line_total_amount' => 8997,
                        ],
                    ],
                    'item_count' => 1,
                    'total_amount' => 8997,
                ],
            ]);
    }

    public function test_add_with_an_unknown_cart_token_returns_a_new_backend_issued_token(): void
    {
        [$siteDomain, $productId] = $this->seedCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');
        $unknownToken = 'unknown-cart-token';

        $response = $this->withHeader($tokenHeader, $unknownToken)
            ->postJson("http://{$siteDomain}/v1/cart/items", [
                'product_id' => $productId,
                'quantity' => 1,
            ]);

        $response
            ->assertOk()
            ->assertHeader($tokenHeader);

        $issuedToken = (string) $response->headers->get($tokenHeader);

        $this->assertNotSame($unknownToken, $issuedToken);

        $response->assertExactJson([
            'data' => [
                'lines' => [
                    [
                        'product_id' => $productId,
                        'name' => 'Whey Protein',
                        'quantity' => 1,
                        'unit_price_amount' => 2999,
                        'line_total_amount' => 2999,
                    ],
                ],
                'item_count' => 1,
                'total_amount' => 2999,
            ],
        ]);
    }

    public function test_returns_a_validation_error_when_the_requested_quantity_exceeds_available_stock(): void
    {
        [$siteDomain, $productId] = $this->seedCatalogForSite('fr', 2);

        $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 3,
        ])
            ->assertStatus(422)
            ->assertExactJson([
                'message' => 'Requested quantity exceeds available stock.',
            ]);
    }

    public function test_returns_a_validation_error_when_the_final_quantity_exceeds_available_stock(): void
    {
        [$siteDomain, $productId] = $this->seedCatalogForSite('fr', 2);
        $tokenHeader = (string) config('cart.token_header');

        $token = $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ])->headers->get($tokenHeader);

        $this->withHeader($tokenHeader, (string) $token)
            ->postJson("http://{$siteDomain}/v1/cart/items", [
                'product_id' => $productId,
                'quantity' => 2,
            ])
            ->assertStatus(422)
            ->assertExactJson([
                'message' => 'Requested quantity exceeds available stock.',
            ]);
    }

    public function test_failing_to_add_an_unpriced_product_does_not_mutate_the_existing_cart(): void
    {
        [$siteDomain, $productId] = $this->seedCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');

        $token = $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ])->headers->get($tokenHeader);

        $unpricedProductId = DB::table('products')->insertGetId([
            Product::NAME => 'Creatine',
            Product::STOCK => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader($tokenHeader, (string) $token)
            ->postJson("http://{$siteDomain}/v1/cart/items", [
                'product_id' => $unpricedProductId,
                'quantity' => 1,
            ])
            ->assertNotFound();

        $this->withHeader($tokenHeader, (string) $token)
            ->getJson("http://{$siteDomain}/v1/cart")
            ->assertOk()
            ->assertHeader($tokenHeader, (string) $token)
            ->assertExactJson([
                'data' => [
                    'lines' => [
                        [
                            'product_id' => $productId,
                            'name' => 'Whey Protein',
                            'quantity' => 1,
                            'unit_price_amount' => 2999,
                            'line_total_amount' => 2999,
                        ],
                    ],
                    'item_count' => 1,
                    'total_amount' => 2999,
                ],
            ]);
    }

    /**
     * @return array{string, int}
     */
    private function seedCatalogForSite(string $siteCode, int $stock = 10): array
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
            Product::STOCK => $stock,
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

        return [$siteDomain, $productId];
    }
}
