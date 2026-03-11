<?php

namespace Tests\Feature\Api\Checkout;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Cart\Services\CartStorageService;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\DeliveryMethodEnum;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Enums\PaymentMethodEnum;
use App\Domain\Orders\Events\OrderPlacedEvent;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderLine;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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
        Event::fake([OrderPlacedEvent::class]);

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
            ProductSitePrice::PRICE_AMOUNT_CENTS => 2999,
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
            ])
            ->assertJsonPath('data.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.payment_method', 'BANK_TRANSFER')
            ->assertJsonPath('data.delivery_method', 'HOME_DELIVERY')
            ->assertJsonPath('data.delivery_amount', '0.00')
            ->assertJsonPath('data.total_amount', '29.99');

        $this->assertIsArray($response->json('data.lines'));
        $this->assertMatchesRegularExpression('/^FR-\d{6}$/', (string) $response->json('data.reference'));

        $orderId = (int) $response->json('data.id');

        $this->assertDatabaseHas('orders', [
            Order::ID => $orderId,
            Order::SITE_ID => $siteId,
            Order::CUSTOMER_ID => $customer->getKey(),
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT->value,
            Order::PAYMENT_METHOD => PaymentMethodEnum::BANK_TRANSFER->value,
            Order::DELIVERY_METHOD => DeliveryMethodEnum::HOME_DELIVERY->value,
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 2999,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
        ]);

        $this->assertDatabaseHas('order_lines', [
            OrderLine::ORDER_ID => $orderId,
            OrderLine::PRODUCT_ID => $productId,
            OrderLine::PRODUCT_NAME => 'Whey Protein',
            OrderLine::UNIT_PRICE_AMOUNT_CENTS => 2999,
            OrderLine::QUANTITY => 1,
            OrderLine::LINE_TOTAL_AMOUNT_CENTS => 2999,
        ]);

        $this->assertSame(9, Product::query()->whereKey($productId)->value(Product::STOCK));
        $this->assertNull(
            Cache::get(app(CartStorageService::class)->makeKey('fr', $cartToken)),
        );

        Event::assertDispatched(OrderPlacedEvent::class, function (OrderPlacedEvent $event) use ($orderId, $customer, $siteId): bool {
            return (int) $event->order->getKey() === $orderId
                && (int) $event->order->getAttribute(Order::CUSTOMER_ID) === (int) $customer->getKey()
                && (int) $event->order->getAttribute(Order::SITE_ID) === $siteId;
        });
    }

    public function test_returns_a_validation_error_when_checkout_is_attempted_with_an_empty_cart(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $response = $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->postJson("http://{$siteDomain}/v1/checkout", [
                'full_name' => 'Marie Dupont',
                'full_address' => '12 Rue de Paris',
                'city' => 'Paris',
                'country' => 'France',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Checkout requires a non-empty cart.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
    }

    public function test_returns_a_validation_error_when_stock_changed_since_the_cart_was_built(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $productId = DB::table('products')->insertGetId([
            Product::NAME => 'Whey Protein',
            Product::STOCK => 1,
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

        $cartToken = (string) $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ])->headers->get((string) config('cart.token_header'));

        Product::query()->whereKey($productId)->update([
            Product::STOCK => 0,
        ]);

        $response = $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->withHeader((string) config('cart.token_header'), $cartToken)
            ->postJson("http://{$siteDomain}/v1/checkout", [
                'full_name' => 'Marie Dupont',
                'full_address' => '12 Rue de Paris',
                'city' => 'Paris',
                'country' => 'France',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Requested quantity exceeds available stock.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
        $this->assertSame(0, Product::query()->whereKey($productId)->value(Product::STOCK));
        $this->assertNotNull(
            Cache::get(app(CartStorageService::class)->makeKey('fr', $cartToken)),
        );
    }

    public function test_returns_a_validation_error_when_a_cart_line_is_no_longer_available_for_checkout(): void
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
            ProductSitePrice::PRICE_AMOUNT_CENTS => 2999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cartToken = (string) $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ])->headers->get((string) config('cart.token_header'));

        DB::table('product_site_prices')
            ->where(ProductSitePrice::PRODUCT_ID, $productId)
            ->where(ProductSitePrice::SITE_ID, $siteId)
            ->delete();

        $response = $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->withHeader((string) config('cart.token_header'), $cartToken)
            ->postJson("http://{$siteDomain}/v1/checkout", [
                'full_name' => 'Marie Dupont',
                'full_address' => '12 Rue de Paris',
                'city' => 'Paris',
                'country' => 'France',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'One or more cart items are no longer available for checkout.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
        $this->assertSame(10, Product::query()->whereKey($productId)->value(Product::STOCK));
        $this->assertNotNull(
            Cache::get(app(CartStorageService::class)->makeKey('fr', $cartToken)),
        );
    }

    public function test_rejects_checkout_on_a_different_resolved_site(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');
        [, $otherSiteDomain] = TestDataHelper::seedSite('it');

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
            ProductSitePrice::PRICE_AMOUNT_CENTS => 2999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cartToken = (string) $this->postJson("http://{$siteDomain}/v1/cart/items", [
            'product_id' => $productId,
            'quantity' => 1,
        ])->headers->get((string) config('cart.token_header'));

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->withHeader((string) config('cart.token_header'), $cartToken)
            ->postJson("http://{$otherSiteDomain}/v1/checkout", [
                'full_name' => 'Marie Dupont',
                'full_address' => '12 Rue de Paris',
                'city' => 'Paris',
                'country' => 'France',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_lines', 0);
        $this->assertSame(10, Product::query()->whereKey($productId)->value(Product::STOCK));
        $this->assertNotNull(
            Cache::get(app(CartStorageService::class)->makeKey('fr', $cartToken)),
        );
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
