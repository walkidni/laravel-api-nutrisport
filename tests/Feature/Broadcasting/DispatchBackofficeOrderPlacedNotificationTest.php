<?php

namespace Tests\Feature\Broadcasting;

use App\Domain\Backoffice\Events\BackofficeOrderPlacedNotification;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Events\OrderPlacedEvent;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Listeners\NotifyBackofficeOfNewOrderListener;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Support\TestDataHelper;
use Tests\TestCase;

class DispatchBackofficeOrderPlacedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_a_backoffice_broadcast_notification_with_the_approved_order_summary_contract(): void
    {
        Event::fake([BackofficeOrderPlacedNotification::class]);

        [$siteId] = TestDataHelper::seedSite('fr');

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
            Order::TOTAL_AMOUNT_CENTS => 2999,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
        ]);

        app(NotifyBackofficeOfNewOrderListener::class)->handle(new OrderPlacedEvent($order));

        Event::assertDispatched(BackofficeOrderPlacedNotification::class, function (BackofficeOrderPlacedNotification $event) use ($order): bool {
            $channels = $event->broadcastOn();

            if (count($channels) !== 1 || ! $channels[0] instanceof PrivateChannel) {
                return false;
            }

            return $channels[0]->name === 'private-backoffice.orders'
                && $event->broadcastWith() === [
                    'id' => (int) $order->getKey(),
                    'customer_name' => 'Marie Dupont',
                    'total_amount' => '29.99',
                    'status' => 'PENDING_PAYMENT',
                    'remaining_amount' => '29.99',
                ];
        });
    }
}
