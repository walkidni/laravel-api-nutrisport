<?php

namespace App\Http\Resources\Api\Backoffice;

use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Shared\Services\MoneyFormatterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackofficeRecentOrderResource extends JsonResource
{
    public function __construct(Order $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{id:int, customer_name:string, total_amount:string, status:string, remaining_amount:string}
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;
        /** @var OrderStatusEnum|string $status */
        $status = $order->getAttribute(Order::STATUS);
        $formattedTotal = app(MoneyFormatterService::class)->formatCents(
            (int) $order->getAttribute(Order::TOTAL_AMOUNT_CENTS),
        );

        return [
            Order::ID => (int) $order->getKey(),
            'customer_name' => (string) $order->getAttribute(Order::FULL_NAME),
            'total_amount' => $formattedTotal,
            Order::STATUS => $status instanceof OrderStatusEnum ? $status->value : (string) $status,
            'remaining_amount' => $formattedTotal,
        ];
    }
}
