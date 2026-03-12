<?php

namespace Tests\Feature\Reporting;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\DeliveryMethodEnum;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Enums\PaymentMethodEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderLine;
use App\Domain\Reporting\Actions\BuildDailyReportAction;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\TestDataHelper;
use Tests\TestCase;

class BuildDailyReportActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_the_previous_calendar_day_report_data(): void
    {
        config()->set('app.timezone', 'Africa/Casablanca');

        Carbon::setTestNow(Carbon::parse('2026-03-12 00:05:00', 'Africa/Casablanca'));

        try {
            [$frSiteId] = TestDataHelper::seedSite('fr');
            [$itSiteId] = TestDataHelper::seedSite('it');
            [$beSiteId] = TestDataHelper::seedSite('be');

            $mostSoldAndHighestTurnoverProduct = Product::factory()->create([
                Product::NAME => 'Mass Gainer',
            ]);

            $higherIdTieForHighestMetrics = Product::factory()->create([
                Product::NAME => 'Recovery Drink',
            ]);

            $leastSoldProduct = Product::factory()->create([
                Product::NAME => 'Creatine',
            ]);

            $lowestTurnoverProduct = Product::factory()->create([
                Product::NAME => 'BCAA',
            ]);

            $higherIdTieForLowestTurnover = Product::factory()->create([
                Product::NAME => 'Collagen',
            ]);

            $outsideWindowProduct = Product::factory()->create([
                Product::NAME => 'Omega 3',
            ]);

            $renamedProduct = Product::factory()->create([
                Product::NAME => 'Hydration Mix',
            ]);

            $this->createOrderWithLine(
                siteId: $frSiteId,
                reference: 'FR-000001',
                totalAmountCents: 7000,
                createdAt: '2026-03-11 00:00:00',
                productId: (int) $higherIdTieForHighestMetrics->getKey(),
                productName: 'Recovery Drink',
                quantity: 10,
                lineTotalAmountCents: 7000,
            );

            $this->createOrderWithLine(
                siteId: $frSiteId,
                reference: 'FR-000002',
                totalAmountCents: 1000,
                createdAt: '2026-03-11 10:15:00',
                productId: (int) $leastSoldProduct->getKey(),
                productName: 'Creatine',
                quantity: 1,
                lineTotalAmountCents: 1000,
            );

            $this->createOrderWithLine(
                siteId: $frSiteId,
                reference: 'FR-000003',
                totalAmountCents: 1500,
                createdAt: '2026-03-11 15:30:00',
                productId: (int) $lowestTurnoverProduct->getKey(),
                productName: 'BCAA',
                quantity: 1,
                lineTotalAmountCents: 500,
            );

            $this->createOrderWithLine(
                siteId: $itSiteId,
                reference: 'IT-000001',
                totalAmountCents: 500,
                createdAt: '2026-03-11 18:20:00',
                productId: (int) $higherIdTieForLowestTurnover->getKey(),
                productName: 'Collagen',
                quantity: 1,
                lineTotalAmountCents: 500,
            );

            $this->createOrderWithLine(
                siteId: $beSiteId,
                reference: 'BE-000001',
                totalAmountCents: 7000,
                createdAt: '2026-03-11 23:59:59',
                productId: (int) $mostSoldAndHighestTurnoverProduct->getKey(),
                productName: 'Mass Gainer',
                quantity: 10,
                lineTotalAmountCents: 7000,
                orderStatus: 'PAID',
            );

            $this->createOrderWithLine(
                siteId: $beSiteId,
                reference: 'BE-000002',
                totalAmountCents: 4500,
                createdAt: '2026-03-11 21:00:00',
                productId: (int) $outsideWindowProduct->getKey(),
                productName: 'Omega 3',
                quantity: 2,
                lineTotalAmountCents: 4500,
            );

            $this->createOrderWithLine(
                siteId: $itSiteId,
                reference: 'IT-000002',
                totalAmountCents: 1200,
                createdAt: '2026-03-11 22:00:00',
                productId: (int) $renamedProduct->getKey(),
                productName: 'Hydration Mix Classic',
                quantity: 1,
                lineTotalAmountCents: 400,
            );

            $this->createOrderWithLine(
                siteId: $itSiteId,
                reference: 'IT-000003',
                totalAmountCents: 1800,
                createdAt: '2026-03-11 22:30:00',
                productId: (int) $renamedProduct->getKey(),
                productName: 'Hydration Mix Rebrand',
                quantity: 2,
                lineTotalAmountCents: 600,
            );

            $this->createOrderWithLine(
                siteId: $beSiteId,
                reference: 'BE-000003',
                totalAmountCents: 9999,
                createdAt: '2026-03-12 00:00:00',
                productId: (int) $mostSoldAndHighestTurnoverProduct->getKey(),
                productName: 'Mass Gainer',
                quantity: 99,
                lineTotalAmountCents: 9999,
            );

            $this->createOrderWithLine(
                siteId: $beSiteId,
                reference: 'BE-000004',
                totalAmountCents: 8888,
                createdAt: '2026-03-10 23:59:59',
                productId: (int) $outsideWindowProduct->getKey(),
                productName: 'Omega 3',
                quantity: 88,
                lineTotalAmountCents: 8888,
            );

            $report = app(BuildDailyReportAction::class)();

            $this->assertSame('2026-03-11', $report->reportDate->toDateString());

            $this->assertNotNull($report->mostSoldProduct);
            $this->assertSame((int) $mostSoldAndHighestTurnoverProduct->getKey(), $report->mostSoldProduct->productId);
            $this->assertSame('Mass Gainer', $report->mostSoldProduct->productName);
            $this->assertSame(10, $report->mostSoldProduct->value);

            $this->assertNotNull($report->leastSoldProduct);
            $this->assertSame((int) $leastSoldProduct->getKey(), $report->leastSoldProduct->productId);
            $this->assertSame('Creatine', $report->leastSoldProduct->productName);
            $this->assertSame(1, $report->leastSoldProduct->value);

            $this->assertNotNull($report->highestTurnoverProduct);
            $this->assertSame((int) $mostSoldAndHighestTurnoverProduct->getKey(), $report->highestTurnoverProduct->productId);
            $this->assertSame('Mass Gainer', $report->highestTurnoverProduct->productName);
            $this->assertSame(7000, $report->highestTurnoverProduct->value);

            $this->assertNotNull($report->lowestTurnoverProduct);
            $this->assertSame((int) $lowestTurnoverProduct->getKey(), $report->lowestTurnoverProduct->productId);
            $this->assertSame('BCAA', $report->lowestTurnoverProduct->productName);
            $this->assertSame(500, $report->lowestTurnoverProduct->value);

            $this->assertSame([
                ['site_code' => 'fr', 'turnover_amount_cents' => 9500],
                ['site_code' => 'it', 'turnover_amount_cents' => 3500],
                ['site_code' => 'be', 'turnover_amount_cents' => 11500],
            ], array_map(
                fn ($siteTurnover) => [
                    'site_code' => $siteTurnover->siteCode,
                    'turnover_amount_cents' => $siteTurnover->turnoverAmountCents,
                ],
                $report->siteTurnovers,
            ));

            $this->assertNotSame((int) $renamedProduct->getKey(), $report->leastSoldProduct->productId);
            $this->assertNotSame((int) $renamedProduct->getKey(), $report->lowestTurnoverProduct->productId);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_builds_an_empty_day_report_with_null_product_metrics_and_zeroed_site_turnovers(): void
    {
        config()->set('app.timezone', 'Africa/Casablanca');

        Carbon::setTestNow(Carbon::parse('2026-03-12 00:05:00', 'Africa/Casablanca'));

        try {
            [$frSiteId] = TestDataHelper::seedSite('fr');
            TestDataHelper::seedSite('it');
            TestDataHelper::seedSite('be');

            $outsideWindowProduct = Product::factory()->create([
                Product::NAME => 'Casein',
            ]);

            $this->createOrderWithLine(
                siteId: $frSiteId,
                reference: 'FR-000010',
                totalAmountCents: 4200,
                createdAt: '2026-03-12 08:00:00',
                productId: (int) $outsideWindowProduct->getKey(),
                productName: 'Casein',
                quantity: 2,
                lineTotalAmountCents: 4200,
            );

            $report = app(BuildDailyReportAction::class)();

            $this->assertSame('2026-03-11', $report->reportDate->toDateString());
            $this->assertNull($report->mostSoldProduct);
            $this->assertNull($report->leastSoldProduct);
            $this->assertNull($report->highestTurnoverProduct);
            $this->assertNull($report->lowestTurnoverProduct);
            $this->assertSame([
                ['site_code' => 'fr', 'turnover_amount_cents' => 0],
                ['site_code' => 'it', 'turnover_amount_cents' => 0],
                ['site_code' => 'be', 'turnover_amount_cents' => 0],
            ], array_map(
                fn ($siteTurnover) => [
                    'site_code' => $siteTurnover->siteCode,
                    'turnover_amount_cents' => $siteTurnover->turnoverAmountCents,
                ],
                $report->siteTurnovers,
            ));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createOrderWithLine(
        int $siteId,
        string $reference,
        int $totalAmountCents,
        string $createdAt,
        int $productId,
        string $productName,
        int $quantity,
        int $lineTotalAmountCents,
        string $orderStatus = OrderStatusEnum::PENDING_PAYMENT->value,
    ): void {
        $createdAtCarbon = Carbon::parse($createdAt, config('app.timezone'));

        $customer = Customer::factory()->create([
            Customer::SITE_ID => $siteId,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            Order::SITE_ID => $siteId,
            Order::CUSTOMER_ID => (int) $customer->getKey(),
            Order::REFERENCE_SEQUENCE => (int) preg_replace('/\D/', '', $reference),
            Order::REFERENCE => $reference,
            Order::STATUS => $orderStatus,
            Order::PAYMENT_METHOD => PaymentMethodEnum::BANK_TRANSFER->value,
            Order::DELIVERY_METHOD => DeliveryMethodEnum::HOME_DELIVERY->value,
            Order::DELIVERY_AMOUNT_CENTS => 0,
            Order::TOTAL_AMOUNT_CENTS => $totalAmountCents,
            Order::FULL_NAME => 'Marie Dupont',
            Order::FULL_ADDRESS => '12 Rue de Paris',
            Order::CITY => 'Paris',
            Order::COUNTRY => 'France',
            Order::CREATED_AT => $createdAtCarbon,
            'updated_at' => $createdAtCarbon,
        ]);

        DB::table('order_lines')->insert([
            OrderLine::ORDER_ID => $orderId,
            OrderLine::PRODUCT_ID => $productId,
            OrderLine::PRODUCT_NAME => $productName,
            OrderLine::UNIT_PRICE_AMOUNT_CENTS => intdiv($lineTotalAmountCents, $quantity),
            OrderLine::QUANTITY => $quantity,
            OrderLine::LINE_TOTAL_AMOUNT_CENTS => $lineTotalAmountCents,
            'created_at' => $createdAtCarbon,
            'updated_at' => $createdAtCarbon,
        ]);
    }
}
