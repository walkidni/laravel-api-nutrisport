<?php

namespace App\Domain\Reporting\DTOs;

use Carbon\CarbonImmutable;

final readonly class DailyReportingWindowDTO
{
    public function __construct(
        public CarbonImmutable $reportDate,
        public CarbonImmutable $startsAt,
        public CarbonImmutable $endsAt,
    ) {
    }
}
