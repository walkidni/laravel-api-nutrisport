<?php

namespace App\Domain\Backoffice\Services;

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Models\BackofficeRefreshToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BackofficeRefreshTokenService
{
    public function consume(
        string $plainToken,
        ?Carbon $revokedAt = null,
    ): ?BackofficeRefreshToken {
        $refreshToken = BackofficeRefreshToken::query()
            ->where(BackofficeRefreshToken::TOKEN_HASH, hash('sha256', $plainToken))
            ->lockForUpdate()
            ->first();

        if (! $refreshToken instanceof BackofficeRefreshToken || ! $this->isUsable($refreshToken)) {
            return null;
        }

        $refreshToken->forceFill([
            BackofficeRefreshToken::REVOKED_AT => $revokedAt ?? now(),
        ])->save();

        return $refreshToken;
    }

    public function isUsable(BackofficeRefreshToken $refreshToken): bool
    {
        return $refreshToken->getAttribute(BackofficeRefreshToken::REVOKED_AT) === null
            && $refreshToken->getAttribute(BackofficeRefreshToken::EXPIRES_AT)->isFuture()
            && $refreshToken->getAttribute(BackofficeRefreshToken::ABSOLUTE_EXPIRES_AT)->isFuture();
    }

    /**
     * @return array{BackofficeRefreshToken, string}
     */
    public function issue(
        BackofficeAgent $agent,
        ?Carbon $issuedAt = null,
        ?Carbon $absoluteExpiresAt = null,
    ): array {
        $issuedAt ??= now();
        $absoluteExpiresAt ??= $issuedAt->copy()->addMinutes(config('auth.backoffice.absolute_session_ttl'));

        $expiresAt = $issuedAt->copy()->addMinutes(config('auth.backoffice.refresh_token_ttl'));

        if ($expiresAt->greaterThan($absoluteExpiresAt)) {
            $expiresAt = $absoluteExpiresAt->copy();
        }

        $plainToken = Str::random(80);

        $refreshToken = BackofficeRefreshToken::query()->create([
            BackofficeRefreshToken::BACKOFFICE_AGENT_ID => (int) $agent->getKey(),
            BackofficeRefreshToken::TOKEN_HASH => hash('sha256', $plainToken),
            BackofficeRefreshToken::ISSUED_AT => $issuedAt,
            BackofficeRefreshToken::EXPIRES_AT => $expiresAt,
            BackofficeRefreshToken::ABSOLUTE_EXPIRES_AT => $absoluteExpiresAt,
            BackofficeRefreshToken::REVOKED_AT => null,
        ]);

        return [$refreshToken, $plainToken];
    }
}
