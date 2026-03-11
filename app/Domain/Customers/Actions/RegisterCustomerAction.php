<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterCustomerAction
{
    /**
     * @param array{email: string, password: string} $validated
     */
    public function __invoke(Site $site, array $validated): Customer
    {
        try {
            return Customer::query()->create([
                Customer::SITE_ID => (int) $site->getKey(),
                Customer::EMAIL => $validated[Customer::EMAIL],
                Customer::PASSWORD => Hash::make($validated[Customer::PASSWORD]),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                Customer::EMAIL => __('validation.unique', ['attribute' => Customer::EMAIL]),
            ]);
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');

        return in_array($sqlState, ['23000', '23505'], true);
    }
}
