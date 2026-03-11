<?php

namespace App\Domain\Customers\Queries;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Models\Order;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\Eloquent\Collection;

final class ListCustomerOrdersQuery
{
    /**
     * @return Collection<int, Order>
     */
    public function __invoke(Customer $customer, Site $site): Collection
    {
        return Order::query()
            ->where(Order::SITE_ID, $site->getKey())
            ->where(Order::CUSTOMER_ID, $customer->getKey())
            ->orderByDesc('created_at')
            ->get([
                Order::ID,
                Order::REFERENCE,
                Order::STATUS,
                Order::TOTAL_AMOUNT_CENTS,
                'created_at',
            ]);
    }
}
