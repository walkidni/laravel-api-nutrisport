<?php

namespace App\Domain\Reporting\Actions;

use App\Domain\Reporting\DTOs\DailyReportDTO;
use App\Domain\Reporting\Queries\ListDailyProductQuantityRankingQuery;
use App\Domain\Reporting\Queries\ListDailyProductTurnoverRankingQuery;
use App\Domain\Reporting\Queries\ListDailySiteTurnoverQuery;
use App\Domain\Reporting\Services\DailyReportingWindowFactory;

final class BuildDailyReportAction
{
    public function __construct(
        private readonly DailyReportingWindowFactory $dailyReportingWindowFactory,
        private readonly ListDailyProductQuantityRankingQuery $listDailyProductQuantityRankingQuery,
        private readonly ListDailyProductTurnoverRankingQuery $listDailyProductTurnoverRankingQuery,
        private readonly ListDailySiteTurnoverQuery $listDailySiteTurnoverQuery,
    ) {
    }

    public function __invoke(): DailyReportDTO
    {
        $window = ($this->dailyReportingWindowFactory)();
        $quantityRanking = ($this->listDailyProductQuantityRankingQuery)($window);
        $turnoverRanking = ($this->listDailyProductTurnoverRankingQuery)($window);

        return new DailyReportDTO(
            reportDate: $window->reportDate,
            mostSoldProduct: $quantityRanking['descending'],
            leastSoldProduct: $quantityRanking['ascending'],
            highestTurnoverProduct: $turnoverRanking['descending'],
            lowestTurnoverProduct: $turnoverRanking['ascending'],
            siteTurnovers: ($this->listDailySiteTurnoverQuery)($window),
        );
    }
}
