<?php

namespace App\Domain\Backoffice\Events;

use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Shared\Services\MoneyFormatterService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackofficeOrderPlacedNotification implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('backoffice.orders'),
        ];
    }

    /**
     * @return array{id:int, customer_name:string, total_amount:string, status:string, remaining_amount:string}
     */
    public function broadcastWith(): array
    {
        /** @var OrderStatusEnum|string $status */
        $status = $this->order->getAttribute(Order::STATUS);
        $formattedTotal = app(MoneyFormatterService::class)->formatCents(
            (int) $this->order->getAttribute(Order::TOTAL_AMOUNT_CENTS),
        );

        return [
            Order::ID => (int) $this->order->getKey(),
            'customer_name' => (string) $this->order->getAttribute(Order::FULL_NAME),
            'total_amount' => $formattedTotal,
            Order::STATUS => $status instanceof OrderStatusEnum ? $status->value : (string) $status,
            'remaining_amount' => $formattedTotal,
        ];
    }
}
