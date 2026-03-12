<?php

namespace App\Domain\Backoffice\Queries;

use App\Domain\Orders\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListRecentOrdersQuery
{
    public function __invoke(int $perPage, int $page): LengthAwarePaginator
    {
        return Order::query()
            ->where(Order::CREATED_AT, '>=', now()->subDays(5))
            ->orderByDesc(Order::CREATED_AT)
            ->paginate(
                $perPage,
                [
                    Order::ID,
                    Order::FULL_NAME,
                    Order::STATUS,
                    Order::TOTAL_AMOUNT_CENTS,
                    Order::CREATED_AT,
                ],
                'page',
                $page,
            );
    }
}
