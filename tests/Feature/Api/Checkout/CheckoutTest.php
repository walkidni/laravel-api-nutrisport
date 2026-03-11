<?php

namespace Tests\Feature\Api\Checkout;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestDataHelper;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_defines_the_checkout_contract_for_the_authenticated_customer_cart(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
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

        $cartToken = (string) $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ])->headers->get((string) config('cart.token_header'));

        $response = $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->withHeader((string) config('cart.token_header'), $cartToken)
            ->postJson("http://{$siteDomain}/v1/checkout", [
                'full_name' => 'Marie Dupont',
                'full_address' => '12 Rue de Paris',
                'city' => 'Paris',
                'country' => 'France',
            ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reference',
                    'status',
                    'payment_method',
                    'delivery_method',
                    'delivery_amount',
                    'total_amount',
                    'lines',
                ],
            ]);

        $this->assertIsArray($response->json('data.lines'));
    }

    private function issueCustomerAccessToken(Customer $customer, int $siteId): string
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('customer');

        return $guard
            ->claims([
                Customer::SITE_ID => $siteId,
                Site::CODE => 'fr',
            ])
            ->login($customer);
    }
}
