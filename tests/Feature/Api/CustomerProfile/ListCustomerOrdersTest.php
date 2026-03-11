<?php

namespace Tests\Feature\Api\CustomerProfile;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\TestDataHelper;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

class ListCustomerOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_lists_newest_first_customer_owned_order_summaries_for_the_resolved_site(): void
    {
        [$siteId, $siteDomain] = TestDataHelper::seedSite('fr');
        [$otherSiteId] = TestDataHelper::seedSite('it');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $otherCustomer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        Order::query()->create([
            Order::SITE_ID => $siteId,
            Order::CUSTOMER_ID => $customer->getKey(),
            Order::REFERENCE_SEQUENCE => 1,
            Order::REFERENCE => 'FR-000001',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 2999,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        Order::query()->create([
            Order::SITE_ID => $siteId,
            Order::CUSTOMER_ID => $customer->getKey(),
            Order::REFERENCE_SEQUENCE => 2,
            Order::REFERENCE => 'FR-000002',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 4999,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Order::query()->create([
            Order::SITE_ID => $siteId,
            Order::CUSTOMER_ID => $otherCustomer->getKey(),
            Order::REFERENCE_SEQUENCE => 3,
            Order::REFERENCE => 'FR-000003',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 1999,
            Order::FULL_NAME => 'Other Customer',
            Order::FULL_ADDRESS => '1 Rue de Lyon',
            Order::CITY => 'Lyon',
            Order::COUNTRY => 'France',
        ]);

        Order::query()->create([
            Order::SITE_ID => $otherSiteId,
            Order::CUSTOMER_ID => $customer->getKey(),
            Order::REFERENCE_SEQUENCE => 1,
            Order::REFERENCE => 'IT-000001',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 9999,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => 'Via Roma 1',
            Order::CITY => 'Rome',
            Order::COUNTRY => 'Italie',
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->getJson("http://{$siteDomain}/v1/me/orders")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'reference',
                        'total_amount',
                        'status',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonPath('data.0.reference', 'FR-000002')
            ->assertJsonPath('data.0.total_amount', '49.99')
            ->assertJsonPath('data.0.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.1.reference', 'FR-000001')
            ->assertJsonPath('data.1.total_amount', '29.99')
            ->assertJsonPath('data.1.status', 'PENDING_PAYMENT');
    }

    public function test_rejects_listing_customer_orders_on_a_different_resolved_site(): void
    {
        [$siteId] = TestDataHelper::seedSite('fr');
        [, $otherSiteDomain] = TestDataHelper::seedSite('it');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $this->withToken($this->issueCustomerAccessToken($customer, $siteId))
            ->getJson("http://{$otherSiteDomain}/v1/me/orders")
            ->assertForbidden();
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
