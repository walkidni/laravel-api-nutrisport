<?php

namespace Tests\Feature\Api\CustomerProfile;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderLine;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\TestDataHelper;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

class ShowCustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_returns_the_customer_owned_site_scoped_order_detail_with_lines(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $whey = Product::factory()->create([
            Product::NAME => 'Whey Protein',
        ]);

        $creatine = Product::factory()->create([
            Product::NAME => 'Creatine',
        ]);

        $order = Order::query()->create([
            Order::SITE_ID => $siteId,
            Order::CUSTOMER_ID => $customer->getKey(),
            Order::REFERENCE_SEQUENCE => 1,
            Order::REFERENCE => 'FR-000001',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 4999,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
        ]);

        OrderLine::query()->create([
            OrderLine::ORDER_ID => $order->getKey(),
            OrderLine::PRODUCT_ID => $whey->getKey(),
            OrderLine::PRODUCT_NAME => 'Whey Protein',
            OrderLine::UNIT_PRICE_AMOUNT_CENTS => 2999,
            OrderLine::QUANTITY => 1,
            OrderLine::LINE_TOTAL_AMOUNT_CENTS => 2999,
        ]);

        OrderLine::query()->create([
            OrderLine::ORDER_ID => $order->getKey(),
            OrderLine::PRODUCT_ID => $creatine->getKey(),
            OrderLine::PRODUCT_NAME => 'Creatine',
            OrderLine::UNIT_PRICE_AMOUNT_CENTS => 2000,
            OrderLine::QUANTITY => 1,
            OrderLine::LINE_TOTAL_AMOUNT_CENTS => 2000,
        ]);

        $response = $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->getJson("http://{$siteDomain}/v1/me/orders/{$order->getKey()}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'reference',
                    'total_amount',
                    'status',
                    'payment_method',
                    'delivery_method',
                    'delivery_amount',
                    'created_at',
                    'full_name',
                    'full_address',
                    'city',
                    'country',
                    'lines' => [
                        '*' => [
                            'product_id',
                            'product_name',
                            'quantity',
                            'unit_price_amount',
                            'line_total_amount',
                        ],
                    ],
                ],
            ])
            ->assertJsonPath('data.id', $order->getKey())
            ->assertJsonPath('data.reference', 'FR-000001')
            ->assertJsonPath('data.total_amount', '49.99')
            ->assertJsonPath('data.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.payment_method', 'BANK_TRANSFER')
            ->assertJsonPath('data.delivery_method', 'HOME_DELIVERY')
            ->assertJsonPath('data.delivery_amount', '0.00')
            ->assertJsonPath('data.full_name', 'Marie Dupont')
            ->assertJsonPath('data.full_address', '12 Rue de Paris')
            ->assertJsonPath('data.city', 'Paris')
            ->assertJsonPath('data.country', 'France');

        $lines = $response->json('data.lines');

        $this->assertIsArray($lines);
        $this->assertCount(2, $lines);
        $this->assertContains([
            'product_id' => $whey->getKey(),
            'product_name' => 'Whey Protein',
            'quantity' => 1,
            'unit_price_amount' => '29.99',
            'line_total_amount' => '29.99',
        ], $lines);
        $this->assertContains([
            'product_id' => $creatine->getKey(),
            'product_name' => 'Creatine',
            'quantity' => 1,
            'unit_price_amount' => '20.00',
            'line_total_amount' => '20.00',
        ], $lines);
    }

    public function test_returns_not_found_when_the_order_is_not_owned_by_the_authenticated_customer(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $otherCustomer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $order = Order::query()->create([
            Order::SITE_ID => $siteId,
            Order::CUSTOMER_ID => $otherCustomer->getKey(),
            Order::REFERENCE_SEQUENCE => 1,
            Order::REFERENCE => 'FR-000001',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 4999,
            Order::FULL_NAME => 'Other Customer',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->getJson("http://{$siteDomain}/v1/me/orders/{$order->getKey()}")
            ->assertNotFound();
    }

    public function test_rejects_showing_customer_order_on_a_different_resolved_site(): void
    {
        [$siteId] = TestDataHelper::seedSite('fr');
        [, $otherSiteDomain] = TestDataHelper::seedSite('it');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $order = Order::query()->create([
            Order::SITE_ID => $siteId,
            Order::CUSTOMER_ID => $customer->getKey(),
            Order::REFERENCE_SEQUENCE => 1,
            Order::REFERENCE => 'FR-000001',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 4999,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->getJson("http://{$otherSiteDomain}/v1/me/orders/{$order->getKey()}")
            ->assertForbidden();
    }

    public function test_returns_not_found_for_the_same_customer_order_on_a_different_site(): void
    {
        [$frSiteId, $frSiteDomain] = TestDataHelper::seedSite('fr');
        [$itSiteId] = TestDataHelper::seedSite('it');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $frSiteId,
        ]);

        $order = Order::query()->create([
            Order::SITE_ID => $itSiteId,
            Order::CUSTOMER_ID => $customer->getKey(),
            Order::REFERENCE_SEQUENCE => 1,
            Order::REFERENCE => 'IT-000001',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 4999,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => 'Via Roma 1',
            Order::CITY => 'Rome',
            Order::COUNTRY => 'Italie',
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $frSiteId))
            ->getJson("http://{$frSiteDomain}/v1/me/orders/{$order->getKey()}")
            ->assertNotFound();
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
