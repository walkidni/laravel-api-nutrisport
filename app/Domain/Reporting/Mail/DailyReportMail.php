<?php

namespace App\Domain\Reporting\Mail;

use App\Domain\Reporting\DTOs\DailyReportDTO;
use App\Domain\Reporting\DTOs\DailyReportProductMetricDTO;
use App\Domain\Reporting\DTOs\DailyReportSiteTurnoverDTO;
use App\Domain\Shared\Services\MoneyFormatterService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class DailyReportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly DailyReportDTO $report,
    ) {
    }

    public function build(): self
    {
        $moneyFormatter = app(MoneyFormatterService::class);
        $formattedReportDate = $this->formattedReportDate();

        return $this
            ->subject('Rapport du '.$formattedReportDate)
            ->text('emails.reporting.daily-report')
            ->with([
                'reportTitle' => 'Rapport du '.$formattedReportDate,
                'formattedReportDate' => $formattedReportDate,
                'mostSoldProductName' => $this->metricName($this->report->mostSoldProduct),
                'mostSoldProductValue' => $this->metricValue($this->report->mostSoldProduct),
                'leastSoldProductName' => $this->metricName($this->report->leastSoldProduct),
                'leastSoldProductValue' => $this->metricValue($this->report->leastSoldProduct),
                'highestTurnoverProductName' => $this->metricName($this->report->highestTurnoverProduct),
                'highestTurnoverProductAmount' => $this->metricAmount(
                    $this->report->highestTurnoverProduct,
                    $moneyFormatter,
                ),
                'lowestTurnoverProductName' => $this->metricName($this->report->lowestTurnoverProduct),
                'lowestTurnoverProductAmount' => $this->metricAmount(
                    $this->report->lowestTurnoverProduct,
                    $moneyFormatter,
                ),
                'siteTurnovers' => array_map(
                    fn (DailyReportSiteTurnoverDTO $siteTurnover): array => [
                        'site_code' => strtoupper($siteTurnover->siteCode),
                        'turnover_amount' => $moneyFormatter->formatCents($siteTurnover->turnoverAmountCents),
                    ],
                    $this->report->siteTurnovers,
                ),
            ]);
    }

    private function formattedReportDate(): string
    {
        return $this->report->reportDate
            ->locale('fr')
            ->translatedFormat('j F Y');
    }

    private function metricName(?DailyReportProductMetricDTO $metric): string
    {
        return $metric?->productName ?? 'aucun';
    }

    private function metricValue(?DailyReportProductMetricDTO $metric): int
    {
        return $metric?->value ?? 0;
    }

    private function metricAmount(
        ?DailyReportProductMetricDTO $metric,
        MoneyFormatterService $moneyFormatter,
    ): string {
        return $moneyFormatter->formatCents($metric?->value ?? 0);
    }
}
