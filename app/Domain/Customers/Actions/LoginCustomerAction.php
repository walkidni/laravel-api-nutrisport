<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\DTOs\CustomerAuthTokensDTO;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerRefreshToken;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class LoginCustomerAction
{
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

        $refreshToken = Str::random(80);
        $issuedAt = now();

        CustomerRefreshToken::query()->create([
            CustomerRefreshToken::CUSTOMER_ID => (int) $customer->getKey(),
            CustomerRefreshToken::SITE_ID => (int) $site->getKey(),
            CustomerRefreshToken::TOKEN_HASH => hash('sha256', $refreshToken),
            CustomerRefreshToken::ISSUED_AT => $issuedAt,
            CustomerRefreshToken::EXPIRES_AT => $issuedAt->copy()->addMinutes(config('auth.customers.refresh_token_ttl')),
            CustomerRefreshToken::ABSOLUTE_EXPIRES_AT => $issuedAt->copy()->addMinutes(config('auth.customers.absolute_session_ttl')),
            CustomerRefreshToken::REVOKED_AT => null,
        ]);

        return new CustomerAuthTokensDTO($accessToken, $refreshToken);
    }
}
