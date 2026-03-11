<?php

namespace App\Domain\Customers\Services;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerRefreshToken;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CustomerRefreshTokenService
{
    public function findForSite(string $plainToken, int $siteId): ?CustomerRefreshToken
    {
        return CustomerRefreshToken::query()
            ->where(CustomerRefreshToken::TOKEN_HASH, hash('sha256', $plainToken))
            ->where(CustomerRefreshToken::SITE_ID, $siteId)
            ->first();
    }

    public function consumeForSite(
        string $plainToken,
        int $siteId,
        ?Carbon $revokedAt = null,
    ): ?CustomerRefreshToken {
        $refreshToken = CustomerRefreshToken::query()
            ->where(CustomerRefreshToken::TOKEN_HASH, hash('sha256', $plainToken))
            ->where(CustomerRefreshToken::SITE_ID, $siteId)
            ->lockForUpdate()
            ->first();

        if (! $refreshToken instanceof CustomerRefreshToken || ! $this->isUsable($refreshToken)) {
            return null;
        }

        $refreshToken->forceFill([
            CustomerRefreshToken::REVOKED_AT => $revokedAt ?? now(),
        ])->save();

        return $refreshToken;
    }

    public function isUsable(CustomerRefreshToken $refreshToken): bool
    {
        return $refreshToken->getAttribute(CustomerRefreshToken::REVOKED_AT) === null
            && $refreshToken->getAttribute(CustomerRefreshToken::EXPIRES_AT)->isFuture()
            && $refreshToken->getAttribute(CustomerRefreshToken::ABSOLUTE_EXPIRES_AT)->isFuture();
    }

    /**
     * @return array{CustomerRefreshToken, string}
     */
    public function issue(
        Customer $customer,
        Site $site,
        ?Carbon $issuedAt = null,
        ?Carbon $absoluteExpiresAt = null,
    ): array {
        $issuedAt ??= now();
        $absoluteExpiresAt ??= $issuedAt->copy()->addMinutes(config('auth.customers.absolute_session_ttl'));

        $expiresAt = $issuedAt->copy()->addMinutes(config('auth.customers.refresh_token_ttl'));

        if ($expiresAt->greaterThan($absoluteExpiresAt)) {
            $expiresAt = $absoluteExpiresAt->copy();
        }

        $plainToken = Str::random(80);

        $refreshToken = CustomerRefreshToken::query()->create([
            CustomerRefreshToken::CUSTOMER_ID => (int) $customer->getKey(),
            CustomerRefreshToken::SITE_ID => (int) $site->getKey(),
            CustomerRefreshToken::TOKEN_HASH => hash('sha256', $plainToken),
            CustomerRefreshToken::ISSUED_AT => $issuedAt,
            CustomerRefreshToken::EXPIRES_AT => $expiresAt,
            CustomerRefreshToken::ABSOLUTE_EXPIRES_AT => $absoluteExpiresAt,
            CustomerRefreshToken::REVOKED_AT => null,
        ]);

        return [$refreshToken, $plainToken];
    }
}
