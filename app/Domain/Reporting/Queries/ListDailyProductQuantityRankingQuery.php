<?php

namespace App\Domain\Reporting\Queries;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderLine;
use App\Domain\Reporting\DTOs\DailyReportProductMetricDTO;
use App\Domain\Reporting\DTOs\DailyReportingWindowDTO;
use Illuminate\Support\Facades\DB;

final class ListDailyProductQuantityRankingQuery
{
    /**
     * @return array{ascending: ?DailyReportProductMetricDTO, descending: ?DailyReportProductMetricDTO}
     */
    public function __invoke(DailyReportingWindowDTO $window): array
    {
        return [
            'ascending' => $this->firstMetricForDirection($window, 'asc'),
            'descending' => $this->firstMetricForDirection($window, 'desc'),
        ];
    }

    private function firstMetricForDirection(
        DailyReportingWindowDTO $window,
        string $direction,
    ): ?DailyReportProductMetricDTO {
        $result = DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->whereBetween('orders.'.Order::CREATED_AT, [$window->startsAt, $window->endsAt])
            ->groupBy(OrderLine::PRODUCT_ID)
            ->orderByRaw(sprintf('SUM(%s) %s', OrderLine::QUANTITY, strtoupper($direction)))
            ->orderBy(OrderLine::PRODUCT_ID)
            ->first([
                OrderLine::PRODUCT_ID.' as product_id',
                DB::raw(sprintf('SUBSTRING_INDEX(GROUP_CONCAT(%s ORDER BY order_lines.%s DESC SEPARATOR \'||\'), \'||\', 1) as product_name', OrderLine::PRODUCT_NAME, OrderLine::ID)),
                DB::raw(sprintf('SUM(%s) as metric_value', OrderLine::QUANTITY)),
            ]);

        if ($result === null) {
            return null;
        }

        return new DailyReportProductMetricDTO(
            productId: (int) $result->product_id,
            productName: (string) $result->product_name,
            value: (int) $result->metric_value,
        );
    }
}
