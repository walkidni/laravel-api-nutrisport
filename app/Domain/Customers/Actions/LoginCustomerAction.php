<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\DTOs\CustomerAuthTokensDTO;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Services\CustomerRefreshTokenService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class LoginCustomerAction
{
    public function __construct(
        private readonly CustomerRefreshTokenService $customerRefreshTokenService,
    ) {
    }

    /**
     * @param array{email: string, password: string} $validated
     */
    public function __invoke(Site $site, array $validated): CustomerAuthTokensDTO
    {
        $customer = Customer::query()
            ->where(Customer::SITE_ID, $site->getKey())
            ->where(Customer::EMAIL, $validated[Customer::EMAIL])
            ->first();

        if (! $customer instanceof Customer || ! Hash::check($validated[Customer::PASSWORD], $customer->getAuthPassword())) {
            throw ValidationException::withMessages([
                Customer::EMAIL => __('auth.failed'),
            ]);
        }

        /** @var JWTGuard $guard */
        $guard = Auth::guard('customer');

        $accessToken = $guard
            ->setTTL(config('auth.customers.access_token_ttl'))
            ->claims([
                Customer::SITE_ID => (int) $site->getKey(),
                Site::CODE => (string) $site->getAttribute(Site::CODE),
            ])
            ->login($customer);

        [, $refreshToken] = $this->customerRefreshTokenService->issue($customer, $site);

        return new CustomerAuthTokensDTO($accessToken, $refreshToken);
    }
}
