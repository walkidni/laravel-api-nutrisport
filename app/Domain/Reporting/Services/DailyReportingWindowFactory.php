<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Reporting\DTOs\DailyReportingWindowDTO;
use Carbon\CarbonImmutable;

final class DailyReportingWindowFactory
{
    public function __invoke(): DailyReportingWindowDTO
    {
        $timezone = (string) config('app.timezone');
        $reportDate = CarbonImmutable::now($timezone)->subDay()->startOfDay();

        return new DailyReportingWindowDTO(
            reportDate: $reportDate,
            startsAt: $reportDate,
            endsAt: $reportDate->endOfDay(),
        );
    }
}
