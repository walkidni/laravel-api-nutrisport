<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Cart\Services\CartStateService;
use App\Domain\Cart\Services\CartStorageService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\DTOs\CheckoutResultDTO;
use App\Domain\Orders\Enums\DeliveryMethodEnum;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Enums\PaymentMethodEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderLine;
use App\Domain\Orders\Services\OrderReferenceService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutAction
{
    public function __construct(
        private readonly CartStateService $cartStateService,
        private readonly CartStorageService $cartStorageService,
        private readonly OrderReferenceService $orderReferenceService,
    ) {
    }

    /**
     * @param array{full_name: string, full_address: string, city: string, country: string, payment_method?: string, delivery_method?: string} $validated
     */
    public function __invoke(
        Site $site,
        Customer $customer,
        Request $request,
        array $validated,
    ): CheckoutResultDTO {
        $siteCode = (string) $site->getAttribute(Site::CODE);
        $cartToken = $this->cartStateService->resolveTokenFromRequest($request);
        $cartLines = $this->cartStateService->loadLines($siteCode, $cartToken);

        /** @var CheckoutResultDTO $checkoutResult */
        $checkoutResult = DB::transaction(function () use ($site, $customer, $validated, $cartLines): CheckoutResultDTO {
            $quantitiesByProductId = [];

            foreach ($cartLines as $line) {
                $quantitiesByProductId[$line['product_id']] = $line['quantity'];
            }

            $pricedProducts = Product::query()
                ->join(
                    'product_site_prices',
                    'product_site_prices.product_id',
                    '=',
                    'products.id',
                )
                ->where('product_site_prices.site_id', $site->getKey())
                ->whereIn('products.id', array_keys($quantitiesByProductId))
                ->lockForUpdate()
                ->get([
                    'products.id as product_id',
                    'products.name',
                    'products.'.Product::STOCK.' as product_stock',
                    'product_site_prices.'.ProductSitePrice::PRICE_AMOUNT_CENTS.' as unit_price_amount_cents',
                ])
                ->keyBy('product_id');

            $orderLines = [];
            $totalAmountCents = 0;

            foreach ($quantitiesByProductId as $productId => $quantity) {
                /** @var Product $pricedProduct */
                $pricedProduct = $pricedProducts->get($productId);

                $unitPriceAmountCents = (int) $pricedProduct->getAttribute('unit_price_amount_cents');
                $lineTotalAmountCents = $unitPriceAmountCents * $quantity;

                $orderLines[] = [
                    OrderLine::PRODUCT_ID => (int) $pricedProduct->getAttribute('product_id'),
                    OrderLine::PRODUCT_NAME => (string) $pricedProduct->getAttribute(Product::NAME),
                    OrderLine::UNIT_PRICE_AMOUNT_CENTS => $unitPriceAmountCents,
                    OrderLine::QUANTITY => $quantity,
                    OrderLine::LINE_TOTAL_AMOUNT_CENTS => $lineTotalAmountCents,
                ];

                $totalAmountCents += $lineTotalAmountCents;
            }

            $referenceSequence = $this->orderReferenceService->nextSequenceForSite($site);
            $order = Order::query()->create([
                Order::SITE_ID => $site->getKey(),
                Order::CUSTOMER_ID => $customer->getKey(),
                Order::REFERENCE_SEQUENCE => $referenceSequence,
                Order::REFERENCE => $this->orderReferenceService->formatForSite($site, $referenceSequence),
                Order::STATUS => OrderStatusEnum::PENDING_PAYMENT,
                Order::PAYMENT_METHOD => $validated[Order::PAYMENT_METHOD] ?? PaymentMethodEnum::BANK_TRANSFER,
                Order::DELIVERY_METHOD => $validated[Order::DELIVERY_METHOD] ?? DeliveryMethodEnum::HOME_DELIVERY,
                Order::DELIVERY_AMOUNT_CENTS => 0,
                Order::TOTAL_AMOUNT_CENTS => $totalAmountCents,
                Order::FULL_NAME => $validated[Order::FULL_NAME],
                Order::FULL_ADDRESS => $validated[Order::FULL_ADDRESS],
                Order::CITY => $validated[Order::CITY],
                Order::COUNTRY => $validated[Order::COUNTRY],
            ]);

            foreach ($orderLines as $orderLine) {
                $order->lines()->create($orderLine);

                Product::query()
                    ->whereKey($orderLine[OrderLine::PRODUCT_ID])
                    ->decrement(Product::STOCK, $orderLine[OrderLine::QUANTITY]);
            }

            return new CheckoutResultDTO(
                id: (int) $order->getKey(),
                reference: (string) $order->getAttribute(Order::REFERENCE),
                status: (string) $order->getAttribute(Order::STATUS)->value,
                paymentMethod: (string) $order->getAttribute(Order::PAYMENT_METHOD)->value,
                deliveryMethod: (string) $order->getAttribute(Order::DELIVERY_METHOD)->value,
                deliveryAmountCents: (int) $order->getAttribute(Order::DELIVERY_AMOUNT_CENTS),
                totalAmountCents: (int) $order->getAttribute(Order::TOTAL_AMOUNT_CENTS),
                lines: $orderLines,
            );
        });

        if (is_string($cartToken) && $cartToken !== '') {
            $this->cartStorageService->forget($siteCode, $cartToken);
        }

        return $checkoutResult;
    }
}
