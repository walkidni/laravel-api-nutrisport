<?php

namespace App\Http\Resources\Api\CustomerProfile;

use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Shared\Services\MoneyFormatterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderSummaryResource extends JsonResource
{
    public function __construct(Order $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{id:int, reference:string, total_amount:string, status:string, created_at:string}
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;
        /** @var OrderStatusEnum|string $status */
        $status = $order->getAttribute(Order::STATUS);

        return [
            Order::ID => (int) $order->getKey(),
            Order::REFERENCE => (string) $order->getAttribute(Order::REFERENCE),
            'total_amount' => app(MoneyFormatterService::class)->formatCents(
                (int) $order->getAttribute(Order::TOTAL_AMOUNT_CENTS),
            ),
            Order::STATUS => $status instanceof OrderStatusEnum ? $status->value : (string) $status,
            'created_at' => $order->getAttribute('created_at')?->toJSON(),
        ];
    }
}
