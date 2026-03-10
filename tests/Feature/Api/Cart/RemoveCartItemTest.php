<?php

namespace Tests\Feature\Api\Cart;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RemoveCartItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_removes_the_requested_line_and_returns_the_updated_cart(): void
    {
        [$siteDomain, $products] = $this->seedCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');
        $token = $this->createCartWithProducts($siteDomain, [
            ['product_id' => $products['whey']['id'], 'quantity' => 1],
            ['product_id' => $products['creatine']['id'], 'quantity' => 2],
        ]);

        $this->withHeader($tokenHeader, $token)
            ->deleteJson("http://{$siteDomain}/v1/cart/items/{$products['whey']['id']}")
            ->assertOk()
            ->assertHeader($tokenHeader, $token)
            ->assertExactJson([
                'data' => [
                    'lines' => [
                        [
                            'product_id' => $products['creatine']['id'],
                            'name' => 'Creatine',
                            'quantity' => 2,
                            'unit_price_amount' => 1999,
                            'line_total_amount' => 3998,
                        ],
                    ],
                    'item_count' => 1,
                    'total_amount' => 3998,
                ],
            ]);
    }

    public function test_removing_a_missing_line_leaves_the_cart_unchanged(): void
    {
        [$siteDomain, $products] = $this->seedCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');
        $token = $this->createCartWithProducts($siteDomain, [
            ['product_id' => $products['whey']['id'], 'quantity' => 1],
        ]);

        $this->withHeader($tokenHeader, $token)
            ->deleteJson("http://{$siteDomain}/v1/cart/items/{$products['creatine']['id']}")
            ->assertOk()
            ->assertHeader($tokenHeader, $token)
            ->assertExactJson([
                'data' => [
                    'lines' => [
                        [
                            'product_id' => $products['whey']['id'],
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

    public function test_removing_the_last_remaining_line_forgets_the_cart_and_drops_the_token(): void
    {
        [$siteDomain, $products] = $this->seedCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');
        $token = $this->createCartWithProducts($siteDomain, [
            ['product_id' => $products['whey']['id'], 'quantity' => 1],
        ]);

        $this->withHeader($tokenHeader, $token)
            ->deleteJson("http://{$siteDomain}/v1/cart/items/{$products['whey']['id']}")
            ->assertOk()
            ->assertHeaderMissing($tokenHeader)
            ->assertExactJson([
                'data' => [
                    'lines' => [],
                    'item_count' => 0,
                    'total_amount' => 0,
                ],
            ]);

        $this->withHeader($tokenHeader, $token)
            ->getJson("http://{$siteDomain}/v1/cart")
            ->assertOk()
            ->assertHeaderMissing($tokenHeader)
            ->assertExactJson([
                'data' => [
                    'lines' => [],
                    'item_count' => 0,
                    'total_amount' => 0,
                ],
            ]);
    }

    /**
     * @return array{string, array<string, array{id: int, price_amount: int}>}
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

        $wheyId = DB::table('products')->insertGetId([
            Product::NAME => 'Whey Protein',
            Product::STOCK => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $creatineId = DB::table('products')->insertGetId([
            Product::NAME => 'Creatine',
            Product::STOCK => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_site_prices')->insert([
            [
                ProductSitePrice::PRODUCT_ID => $wheyId,
                ProductSitePrice::SITE_ID => $siteId,
                ProductSitePrice::PRICE_AMOUNT => 2999,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                ProductSitePrice::PRODUCT_ID => $creatineId,
                ProductSitePrice::SITE_ID => $siteId,
                ProductSitePrice::PRICE_AMOUNT => 1999,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return [
            $siteDomain,
            [
                'whey' => [
                    'id' => $wheyId,
                    'price_amount' => 2999,
                ],
                'creatine' => [
                    'id' => $creatineId,
                    'price_amount' => 1999,
                ],
            ],
        ];
    }

    /**
     * @param array<int, array{product_id: int, quantity: int}> $items
     */
    private function createCartWithProducts(string $siteDomain, array $items): string
    {
        $tokenHeader = (string) config('cart.token_header');
        $token = null;

        foreach ($items as $item) {
            $request = $this;

            if ($token !== null) {
                $request = $this->withHeader($tokenHeader, $token);
            }

            $response = $request->postJson("http://{$siteDomain}/v1/cart/items", $item);
            $token = (string) $response->headers->get($tokenHeader);
        }

        return (string) $token;
    }
}
