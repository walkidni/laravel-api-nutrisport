<?php

namespace Tests\Feature\Api\Backoffice;

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Support\TestDataHelper;
use Tests\TestCase;
use Tymon\JWTAuth\JWTGuard;

class ListRecentOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('jwt.secret', str_repeat('a', 32));
    }

    public function test_lists_recent_orders_across_all_sites_newest_first_with_pagination(): void
    {
        [$frSiteId] = TestDataHelper::seedSite('fr');
        [$itSiteId] = TestDataHelper::seedSite('it');

        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::CAN_VIEW_RECENT_ORDERS => true,
        ]);

        $frCustomer = Customer::factory()->create([
            Customer::SITE_ID => $frSiteId,
        ]);

        $itCustomer = Customer::factory()->create([
            Customer::SITE_ID => $itSiteId,
        ]);

        $olderIncluded = Order::query()->create([
            Order::SITE_ID => $frSiteId,
            Order::CUSTOMER_ID => $frCustomer->getKey(),
            Order::REFERENCE_SEQUENCE => 1,
            Order::REFERENCE => 'FR-000001',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 2999,
            Order::FULL_NAME => 'Older Included',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
        ]);
        $olderIncluded->forceFill([
            Order::CREATED_AT => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ])->save();

        $newestIncluded = Order::query()->create([
            Order::SITE_ID => $itSiteId,
            Order::CUSTOMER_ID => $itCustomer->getKey(),
            Order::REFERENCE_SEQUENCE => 1,
            Order::REFERENCE => 'IT-000001',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 4999,
            Order::FULL_NAME => 'Newest Included',
            Order::FULL_ADDRESS => 'Via Roma 1',
            Order::CITY => 'Rome',
            Order::COUNTRY => 'Italy',
        ]);
        $newestIncluded->forceFill([
            Order::CREATED_AT => now(),
            'updated_at' => now(),
        ])->save();

        $tooOld = Order::query()->create([
            Order::SITE_ID => $frSiteId,
            Order::CUSTOMER_ID => $frCustomer->getKey(),
            Order::REFERENCE_SEQUENCE => 2,
            Order::REFERENCE => 'FR-000002',
            Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
            Order::PAYMENT_METHOD => 'BANK_TRANSFER',
            Order::DELIVERY_METHOD => 'HOME_DELIVERY',
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => 1999,
            Order::FULL_NAME => 'Too Old',
            Order::FULL_ADDRESS => '1 Rue de Lyon',
            Order::CITY => 'Lyon',
            Order::COUNTRY => 'France',
        ]);
        $tooOld->forceFill([
            Order::CREATED_AT => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ])->save();

        $this->withToken($this->issueBackofficeAccessToken($agent))
            ->getJson('/v1/backoffice/orders?per_page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_name',
                        'total_amount',
                        'status',
                        'remaining_amount',
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonPath('data.0.customer_name', 'Newest Included')
            ->assertJsonPath('data.0.total_amount', '49.99')
            ->assertJsonPath('data.0.remaining_amount', '49.99')
            ->assertJsonPath('data.0.status', 'PENDING_PAYMENT')
            ->assertJsonPath('data.1.customer_name', 'Older Included')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_forbids_an_agent_without_recent_orders_permission(): void
    {
        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::CAN_VIEW_RECENT_ORDERS => false,
        ]);

        $this->withToken($this->issueBackofficeAccessToken($agent))
            ->getJson('/v1/backoffice/orders')
            ->assertForbidden();
    }

    public function test_agent_id_one_bypasses_the_recent_orders_permission_check(): void
    {
        $agent = BackofficeAgent::factory()->create([
            BackofficeAgent::ID => 1,
            BackofficeAgent::CAN_VIEW_RECENT_ORDERS => false,
        ]);

        $this->assertSame(1, (int) $agent->getKey());

        $this->withToken($this->issueBackofficeAccessToken($agent))
            ->getJson('/v1/backoffice/orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function issueBackofficeAccessToken(BackofficeAgent $agent): string
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('backoffice');

        return $guard->login($agent);
    }
}
