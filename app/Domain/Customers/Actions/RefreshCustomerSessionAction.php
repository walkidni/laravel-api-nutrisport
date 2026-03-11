<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\DTOs\CustomerAuthTokensDTO;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerRefreshToken;
use App\Domain\Customers\Services\CustomerRefreshTokenService;
use App\Domain\Shared\SiteContext\Site;
use App\Http\Requests\Api\CustomerAuth\RefreshCustomerTokenRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class RefreshCustomerSessionAction
{
    public function __construct(
        private readonly CustomerRefreshTokenService $customerRefreshTokenService,
    ) {
    }

    /**
     * @param array{refresh_token: string} $validated
     */
    public function __invoke(Site $site, array $validated): CustomerAuthTokensDTO
    {
        [$accessToken, $newRefreshToken] = DB::transaction(function () use ($validated, $site): array {
            $currentRefreshToken = $this->customerRefreshTokenService->consumeForSite(
                $validated[RefreshCustomerTokenRequest::REFRESH_TOKEN],
                (int) $site->getKey(),
            );

            if (! $currentRefreshToken instanceof CustomerRefreshToken) {
                throw ValidationException::withMessages([
                    RefreshCustomerTokenRequest::REFRESH_TOKEN => 'Invalid refresh token.',
                ]);
            }

            /** @var Customer $customer */
            $customer = $currentRefreshToken->customer()->firstOrFail();
            $absoluteExpiresAt = $currentRefreshToken->getAttribute(CustomerRefreshToken::ABSOLUTE_EXPIRES_AT);

            [, $newRefreshToken] = $this->customerRefreshTokenService->issue(
                $customer,
                $site,
                now(),
                $absoluteExpiresAt,
            );

            return [$this->issueAccessToken($customer, $site), $newRefreshToken];
        });

        return new CustomerAuthTokensDTO($accessToken, $newRefreshToken);
    }

    private function issueAccessToken(Customer $customer, Site $site): string
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('customer');

        return $guard
            ->setTTL(config('auth.customers.access_token_ttl'))
            ->claims([
                Customer::SITE_ID => (int) $site->getKey(),
                Site::CODE => (string) $site->getAttribute(Site::CODE),
            ])
            ->login($customer);
    }
}
