<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;

final class UpdateCustomerProfileAction
{
    /**
     * @param array<string, mixed> $validated
     */
    public function __invoke(Customer $customer, array $validated): Customer
    {
        $customer->fill($validated);
        $customer->save();

        return $customer->refresh();
    }
}
