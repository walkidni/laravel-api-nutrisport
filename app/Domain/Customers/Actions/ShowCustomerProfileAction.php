<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;

final class ShowCustomerProfileAction
{
    public function __invoke(Customer $customer): Customer
    {
        return $customer;
    }
}
