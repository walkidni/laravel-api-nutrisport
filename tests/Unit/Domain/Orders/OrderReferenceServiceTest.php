<?php

namespace Tests\Unit\Domain\Orders;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\DeliveryMethodEnum;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Enums\PaymentMethodEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Services\OrderReferenceService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestDataHelper;
use Tests\TestCase;

class OrderReferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_allocates_a_site_sequence_then_formats_the_reference_explicitly(): void
    {
        [$siteId] = TestDataHelper::seedSite('fr');
        [$otherSiteId] = TestDataHelper::seedSite('be');

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $otherSiteCustomer = Customer::factory()->create([
            Customer::SITE_ID => $otherSiteId,
        ]);

        $site = Site::query()->findOrFail($siteId);
        $service = app(OrderReferenceService::class);

        $firstSequence = DB::transaction(
            fn (): int => $service->nextSequenceForSite($site),
        );

        $this->assertSame(1, $firstSequence);
        $this->assertSame('FR-000001', $service->formatForSite($site, $firstSequence));

        DB::table('orders')->insert([
            [
                Order::SITE_ID => $siteId,
                Order::CUSTOMER_ID => $customer->getKey(),
                Order::REFERENCE_SEQUENCE => 9,
                Order::REFERENCE => 'FR-000009',
                Order::STATUS => OrderStatusEnum::PENDING_PAYMENT->value,
                Order::PAYMENT_METHOD => PaymentMethodEnum::BANK_TRANSFER->value,
                Order::DELIVERY_METHOD => DeliveryMethodEnum::HOME_DELIVERY->value,
                Order::DELIVERY_AMOUNT_CENTS => 0,
                Order::TOTAL_AMOUNT_CENTS => 2999,
                Order::FULL_NAME => 'Marie Dupont',
                Order::FULL_ADDRESS => '12 Rue de Paris',
                Order::CITY => 'Paris',
                Order::COUNTRY => 'France',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                Order::SITE_ID => $siteId,
                Order::CUSTOMER_ID => $customer->getKey(),
                Order::REFERENCE_SEQUENCE => 10,
                Order::REFERENCE => 'FR-000010',
                Order::STATUS => OrderStatusEnum::PENDING_PAYMENT->value,
                Order::PAYMENT_METHOD => PaymentMethodEnum::BANK_TRANSFER->value,
                Order::DELIVERY_METHOD => DeliveryMethodEnum::HOME_DELIVERY->value,
                Order::DELIVERY_AMOUNT_CENTS => 0,
                Order::TOTAL_AMOUNT_CENTS => 2999,
                Order::FULL_NAME => 'Marie Dupont',
                Order::FULL_ADDRESS => '12 Rue de Paris',
                Order::CITY => 'Paris',
                Order::COUNTRY => 'France',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                Order::SITE_ID => $otherSiteId,
                Order::CUSTOMER_ID => $otherSiteCustomer->getKey(),
                Order::REFERENCE_SEQUENCE => 42,
                Order::REFERENCE => 'BE-000042',
                Order::STATUS => OrderStatusEnum::PENDING_PAYMENT->value,
                Order::PAYMENT_METHOD => PaymentMethodEnum::BANK_TRANSFER->value,
                Order::DELIVERY_METHOD => DeliveryMethodEnum::HOME_DELIVERY->value,
                Order::DELIVERY_AMOUNT_CENTS => 0,
                Order::TOTAL_AMOUNT_CENTS => 2999,
                Order::FULL_NAME => 'Paul Martin',
                Order::FULL_ADDRESS => '7 Rue de Bruxelles',
                Order::CITY => 'Brussels',
                Order::COUNTRY => 'Belgium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $nextSequence = DB::transaction(
            fn (): int => $service->nextSequenceForSite($site),
        );

        $this->assertSame(11, $nextSequence);
        $this->assertSame('FR-000011', $service->formatForSite($site, $nextSequence));
    }
}
