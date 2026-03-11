<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use App\Http\Requests\Api\CustomerProfile\UpdateCustomerPasswordRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class UpdateCustomerPasswordAction
{
    /**
     * @param array<string, mixed> $validated
     */
    public function __invoke(Customer $customer, array $validated): void
    {
        if (! Hash::check((string) $validated[UpdateCustomerPasswordRequest::CURRENT_PASSWORD], $customer->getAttribute(Customer::PASSWORD))) {
            throw ValidationException::withMessages([
                UpdateCustomerPasswordRequest::CURRENT_PASSWORD => 'The current password is incorrect.',
            ]);
        }

        $customer->forceFill([
            Customer::PASSWORD => Hash::make((string) $validated[Customer::PASSWORD]),
        ])->save();
    }
}
