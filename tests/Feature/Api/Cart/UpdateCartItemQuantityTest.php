<?php

namespace Tests\Feature\Api\Cart;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\TestDataHelper;
use Tests\TestCase;

class UpdateCartItemQuantityTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_the_current_empty_cart_when_no_cart_exists(): void
    {
        [$siteDomain, $productId] = TestDataHelper::seedSingleProductCatalogForSite('fr');

        $this->patchJson("http://{$siteDomain}/v1/cart/items/{$productId}", [
            'quantity' => 2,
        ])
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

    public function test_sets_the_absolute_quantity_for_a_cart_line(): void
    {
        [$siteDomain, $productId] = TestDataHelper::seedSingleProductCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');
        $token = $this->createCartWithOneItem($siteDomain, $productId);

        $this->withHeader($tokenHeader, $token)
            ->patchJson("http://{$siteDomain}/v1/cart/items/{$productId}", [
                'quantity' => 2,
            ])
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

    public function test_quantity_zero_removes_the_line_from_the_cart(): void
    {
        [$siteDomain, $productId] = TestDataHelper::seedSingleProductCatalogForSite('fr');
        $tokenHeader = (string) config('cart.token_header');
        $token = $this->createCartWithOneItem($siteDomain, $productId);

        $this->withHeader($tokenHeader, $token)
            ->patchJson("http://{$siteDomain}/v1/cart/items/{$productId}", [
                'quantity' => 0,
            ])
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

    public function test_returns_a_validation_error_when_the_updated_quantity_exceeds_available_stock(): void
    {
        [$siteDomain, $productId] = TestDataHelper::seedSingleProductCatalogForSite('fr', 2);
        $tokenHeader = (string) config('cart.token_header');
        $token = $this->createCartWithOneItem($siteDomain, $productId);

        $this->withHeader($tokenHeader, $token)
            ->patchJson("http://{$siteDomain}/v1/cart/items/{$productId}", [
                'quantity' => 3,
            ])
            ->assertStatus(422)
            ->assertExactJson([
                'message' => 'Requested quantity exceeds available stock.',
            ]);
    }

    private function createCartWithOneItem(string $siteDomain, int $productId): string
    {
        $tokenHeader = (string) config('cart.token_header');

        return (string) $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ])->headers->get($tokenHeader);
    }
}
