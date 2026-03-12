<?php

namespace Tests\Feature\Reporting;

use App\Domain\Catalog\Models\Product;
use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Enums\DeliveryMethodEnum;
use App\Domain\Orders\Enums\OrderStatusEnum;
use App\Domain\Orders\Enums\PaymentMethodEnum;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderLine;
use App\Domain\Reporting\Jobs\SendDailyReportJob;
use App\Domain\Reporting\Mail\DailyReportMail;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Support\TestDataHelper;

class SendDailyReportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_the_previous_calendar_day_report_to_the_configured_administrator(): void
    {
        Mail::fake();

        config()->set('mail.from.address', 'noreply@example.com');
        config()->set('reporting.administrator_email', 'admin@example.com');

        Carbon::setTestNow('2026-03-12 00:05:00');

        try {
            [$frSiteId] = TestDataHelper::seedSite('fr');
            [$beSiteId] = TestDataHelper::seedSite('be');

            $mostSoldProduct = Product::factory()->create([
                Product::NAME => 'Whey Protein',
            ]);

            $leastSoldProduct = Product::factory()->create([
                Product::NAME => 'Creatine',
            ]);

            $highestTurnoverProduct = Product::factory()->create([
                Product::NAME => 'Omega 3',
            ]);

            $lowestTurnoverProduct = Product::factory()->create([
                Product::NAME => 'BCAA',
            ]);

            $nonPendingStatusProduct = Product::factory()->create([
                Product::NAME => 'Mass Gainer',
            ]);

            $this->createOrderWithLine(
                siteId: $frSiteId,
                reference: 'FR-000001',
                totalAmountCents: 6000,
                createdAt: '2026-03-11 10:15:00',
                productId: (int) $mostSoldProduct->getKey(),
                productName: 'Whey Protein',
                quantity: 5,
                lineTotalAmountCents: 5000,
            );

            $this->createOrderWithLine(
                siteId: $beSiteId,
                reference: 'BE-000001',
                totalAmountCents: 4500,
                createdAt: '2026-03-11 16:40:00',
                productId: (int) $highestTurnoverProduct->getKey(),
                productName: 'Omega 3',
                quantity: 2,
                lineTotalAmountCents: 4500,
            );

            $this->createOrderWithLine(
                siteId: $frSiteId,
                reference: 'FR-000002',
                totalAmountCents: 1000,
                createdAt: '2026-03-11 20:10:00',
                productId: (int) $leastSoldProduct->getKey(),
                productName: 'Creatine',
                quantity: 1,
                lineTotalAmountCents: 1000,
            );

            $this->createOrderWithLine(
                siteId: $frSiteId,
                reference: 'FR-000003',
                totalAmountCents: 1500,
                createdAt: '2026-03-11 23:30:00',
                productId: (int) $lowestTurnoverProduct->getKey(),
                productName: 'BCAA',
                quantity: 1,
                lineTotalAmountCents: 500,
            );

            $this->createOrderWithLine(
                siteId: $beSiteId,
                reference: 'BE-000003',
                totalAmountCents: 7000,
                createdAt: '2026-03-11 23:45:00',
                productId: (int) $nonPendingStatusProduct->getKey(),
                productName: 'Mass Gainer',
                quantity: 10,
                lineTotalAmountCents: 7000,
                orderStatus: 'PAID',
            );

            $this->createOrderWithLine(
                siteId: $frSiteId,
                reference: 'FR-000004',
                totalAmountCents: 9999,
                createdAt: '2026-03-12 00:00:00',
                productId: (int) $mostSoldProduct->getKey(),
                productName: 'Whey Protein',
                quantity: 9,
                lineTotalAmountCents: 9999,
            );

            $this->createOrderWithLine(
                siteId: $beSiteId,
                reference: 'BE-000002',
                totalAmountCents: 8888,
                createdAt: '2026-03-10 23:59:59',
                productId: (int) $highestTurnoverProduct->getKey(),
                productName: 'Omega 3',
                quantity: 8,
                lineTotalAmountCents: 8888,
            );

            app(SendDailyReportJob::class)->handle();

            Mail::assertSent(DailyReportMail::class, function (DailyReportMail $mail): bool {
                $mail->assertTo('admin@example.com');
                $mail->assertSeeInOrderInText([
                    'Rapport du 11 mars 2026',
                    'Date',
                    'Produit le plus vendu',
                    'Produit le moins vendu',
                    'Produit au CA maximum',
                    'Produit au CA minimum',
                    'CA par site',
                ]);
                $mail->assertSeeInText('Date : 11 mars 2026');
                $mail->assertSeeInText('Produit le plus vendu : Mass Gainer');
                $mail->assertSeeInText('Produit le moins vendu : Creatine');
                $mail->assertSeeInText('Produit au CA maximum : Mass Gainer');
                $mail->assertSeeInText('Produit au CA minimum : BCAA');
                $mail->assertSeeInText('FR : 85.00');
                $mail->assertSeeInText('BE : 115.00');

                return true;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_sends_an_empty_day_report_when_no_orders_exist_for_the_previous_calendar_day(): void
    {
        Mail::fake();

        config()->set('mail.from.address', 'noreply@example.com');
        config()->set('reporting.administrator_email', 'admin@example.com');

        Carbon::setTestNow('2026-03-12 00:05:00');

        try {
            TestDataHelper::seedSite('fr');
            TestDataHelper::seedSite('it');
            TestDataHelper::seedSite('be');

            $outsideWindowProduct = Product::factory()->create([
                Product::NAME => 'Casein',
            ]);

            $this->createOrderWithLine(
                siteId: Site::query()->where(Site::CODE, 'fr')->value(Site::ID),
                reference: 'FR-000010',
                totalAmountCents: 4200,
                createdAt: '2026-03-12 08:00:00',
                productId: (int) $outsideWindowProduct->getKey(),
                productName: 'Casein',
                quantity: 2,
                lineTotalAmountCents: 4200,
            );

            app(SendDailyReportJob::class)->handle();

            Mail::assertSent(DailyReportMail::class, function (DailyReportMail $mail): bool {
                $mail->assertTo('admin@example.com');
                $mail->assertSeeInOrderInText([
                    'Rapport du 11 mars 2026',
                    'Date',
                    'Produit le plus vendu',
                    'Produit le moins vendu',
                    'Produit au CA maximum',
                    'Produit au CA minimum',
                    'CA par site',
                ]);
                $mail->assertSeeInText('Date : 11 mars 2026');
                $mail->assertSeeInText('Produit le plus vendu : aucun');
                $mail->assertSeeInText('Produit le moins vendu : aucun');
                $mail->assertSeeInText('Produit au CA maximum : aucun');
                $mail->assertSeeInText('Produit au CA minimum : aucun');
                $mail->assertSeeInText('FR : 0.00');
                $mail->assertSeeInText('IT : 0.00');
                $mail->assertSeeInText('BE : 0.00');

                return true;
            });
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
        $createdAtCarbon = Carbon::parse($createdAt);

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
            OrderLine::UNIT_PRICE_AMOUNT_CENTS => (int) round($lineTotalAmountCents / $quantity),
            OrderLine::QUANTITY => $quantity,
            OrderLine::LINE_TOTAL_AMOUNT_CENTS => $lineTotalAmountCents,
            'created_at' => $createdAtCarbon,
            'updated_at' => $createdAtCarbon,
        ]);
    }
}
