<?php

namespace App\Http\Resources\Api\CustomerProfile;

use App\Domain\Orders\Enums\DeliveryMethodEnum;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Enums\PaymentMethodEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderLine;
use App\Domain\Shared\Services\MoneyFormatterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderDetailResource extends JsonResource
{
    public function __construct(Order $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *   id:int,
     *   reference:string,
     *   total_amount:string,
     *   status:string,
     *   payment_method:string,
     *   delivery_method:string,
     *   delivery_amount:string,
     *   created_at:?string,
     *   full_name:string,
     *   full_address:string,
     *   city:string,
     *   country:string,
     *   lines:array<int, array{product_id:int, product_name:string, quantity:int, unit_price_amount:string, line_total_amount:string}>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Order $order */
        $order = $this->resource;
        $moneyFormatter = app(MoneyFormatterService::class);
        /** @var OrderStatusEnum|string $status */
        $status = $order->getAttribute(Order::STATUS);
        /** @var PaymentMethodEnum|string $paymentMethod */
        $paymentMethod = $order->getAttribute(Order::PAYMENT_METHOD);
        /** @var DeliveryMethodEnum|string $deliveryMethod */
        $deliveryMethod = $order->getAttribute(Order::DELIVERY_METHOD);

        return [
            Order::ID => (int) $order->getKey(),
            Order::REFERENCE => (string) $order->getAttribute(Order::REFERENCE),
            'total_amount' => $moneyFormatter->formatCents(
                (int) $order->getAttribute(Order::TOTAL_AMOUNT_CENTS),
            ),
            Order::STATUS => $status instanceof OrderStatusEnum ? $status->value : (string) $status,
            Order::PAYMENT_METHOD => $paymentMethod instanceof PaymentMethodEnum ? $paymentMethod->value : (string) $paymentMethod,
            Order::DELIVERY_METHOD => $deliveryMethod instanceof DeliveryMethodEnum ? $deliveryMethod->value : (string) $deliveryMethod,
            'delivery_amount' => $moneyFormatter->formatCents(
                (int) $order->getAttribute(Order::DELIVERY_AMOUNT_CENTS),
            ),
            Order::CREATED_AT => $order->getAttribute(Order::CREATED_AT)?->toJSON(),
            Order::FULL_NAME => (string) $order->getAttribute(Order::FULL_NAME),
            Order::FULL_ADDRESS => (string) $order->getAttribute(Order::FULL_ADDRESS),
            Order::CITY => (string) $order->getAttribute(Order::CITY),
            Order::COUNTRY => (string) $order->getAttribute(Order::COUNTRY),
            'lines' => $order->lines
                ->map(fn (OrderLine $line): array => [
                    OrderLine::PRODUCT_ID => (int) $line->getAttribute(OrderLine::PRODUCT_ID),
                    OrderLine::PRODUCT_NAME => (string) $line->getAttribute(OrderLine::PRODUCT_NAME),
                    OrderLine::QUANTITY => (int) $line->getAttribute(OrderLine::QUANTITY),
                    'unit_price_amount' => $moneyFormatter->formatCents(
                        (int) $line->getAttribute(OrderLine::UNIT_PRICE_AMOUNT_CENTS),
                    ),
                    'line_total_amount' => $moneyFormatter->formatCents(
                        (int) $line->getAttribute(OrderLine::LINE_TOTAL_AMOUNT_CENTS),
                    ),
                ])
                ->values()
                ->all(),
        ];
    }
}
