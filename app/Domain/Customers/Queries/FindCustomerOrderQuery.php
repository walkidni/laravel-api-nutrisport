<?php

namespace App\Domain\Customers\Queries;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Models\Order;
use App\Domain\Shared\SiteContext\Site;

final class FindCustomerOrderQuery
{
    public function __invoke(Customer $customer, Site $site, int $orderId): ?Order
    {
        return Order::query()
            ->with('lines')
            ->where(Order::SITE_ID, $site->getKey())
            ->where(Order::CUSTOMER_ID, $customer->getKey())
            ->whereKey($orderId)
            ->first();
    }
}
