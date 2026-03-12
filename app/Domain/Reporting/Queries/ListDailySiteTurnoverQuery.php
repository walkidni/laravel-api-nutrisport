<?php

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Models\Order;
use App\Domain\Reporting\DTOs\DailyReportSiteTurnoverDTO;
use App\Domain\Reporting\DTOs\DailyReportingWindowDTO;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Support\Facades\DB;

final class ListDailySiteTurnoverQuery
{
    /**
     * @return array<int, DailyReportSiteTurnoverDTO>
     */
    public function __invoke(DailyReportingWindowDTO $window): array
    {
        return DB::table('sites')
            ->leftJoin('orders', function ($join) use ($window): void {
                $join->on('orders.site_id', '=', 'sites.id')
                    ->whereBetween('orders.'.Order::CREATED_AT, [$window->startsAt, $window->endsAt]);
            })
            ->groupBy('sites.'.Site::ID, 'sites.'.Site::CODE)
            ->orderBy('sites.'.Site::ID)
            ->get([
                'sites.id as site_id',
                'sites.code as site_code',
                DB::raw(sprintf('COALESCE(SUM(orders.%s), 0) as turnover_amount_cents', Order::TOTAL_AMOUNT_CENTS)),
            ])
            ->map(fn ($row) => new DailyReportSiteTurnoverDTO(
                siteId: (int) $row->site_id,
                siteCode: (string) $row->site_code,
                turnoverAmountCents: (int) $row->turnover_amount_cents,
            ))
            ->all();
    }
}
