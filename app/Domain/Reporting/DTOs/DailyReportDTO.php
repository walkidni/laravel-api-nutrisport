<?php

namespace App\Domain\Reporting\DTOs;

use Carbon\CarbonImmutable;

final readonly class DailyReportDTO
{
    /**
     * @param array<int, DailyReportSiteTurnoverDTO> $siteTurnovers
     */
    public function __construct(
        public CarbonImmutable $reportDate,
        public ?DailyReportProductMetricDTO $mostSoldProduct,
        public ?DailyReportProductMetricDTO $leastSoldProduct,
        public ?DailyReportProductMetricDTO $highestTurnoverProduct,
        public ?DailyReportProductMetricDTO $lowestTurnoverProduct,
        public array $siteTurnovers,
    ) {
    }
}
