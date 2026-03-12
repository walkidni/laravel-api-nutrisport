<?php

namespace App\Domain\Reporting\DTOs;

final readonly class DailyReportSiteTurnoverDTO
{
    public function __construct(
        public int $siteId,
        public string $siteCode,
        public int $turnoverAmountCents,
    ) {
    }
}
