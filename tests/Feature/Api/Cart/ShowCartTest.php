<?php

namespace Tests\Feature\Api\Cart;

use App\Domain\Cart\Services\CartStorage;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        DB::table('sites')->insert([
            Site::CODE => $siteCode,
            Site::DOMAIN => $siteDomain,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(CartStorage::class)->put($siteCode, $token, [
            'lines' => [
                [
                    'product_id' => 42,
                    'quantity' => 2,
                ],
            ],
        ]);

        $this->withHeader($tokenHeader, $token)
            ->getJson("http://{$siteDomain}/v1/cart")
            ->assertOk()
            ->assertHeader($tokenHeader, $token)
            ->assertExactJson([
                'data' => [
                    'lines' => [
                        [
                            'product_id' => 42,
                            'quantity' => 2,
                        ],
                    ],
                    'item_count' => 1,
                    'total_amount' => 0,
                ],
            ]);
    }
}
