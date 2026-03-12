<?php

namespace App\Domain\Reporting\DTOs;

final readonly class DailyReportProductMetricDTO
{
    public function __construct(
        public int $productId,
        public string $productName,
        public int $value,
    ) {
    }
}
