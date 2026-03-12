<?php

namespace App\Domain\Backoffice\Actions;

use App\Domain\Backoffice\DTOs\BackofficeAuthTokensDTO;
use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Models\BackofficeRefreshToken;
use App\Domain\Backoffice\Services\BackofficeRefreshTokenService;
use App\Http\Requests\Api\Backoffice\RefreshBackofficeTokenRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class RefreshBackofficeSessionAction
{
    public function __construct(
        private readonly BackofficeRefreshTokenService $backofficeRefreshTokenService,
    ) {
    }

    /**
     * @param array{refresh_token: string} $validated
     */
    public function __invoke(array $validated): BackofficeAuthTokensDTO
    {
        [$accessToken, $newRefreshToken] = DB::transaction(function () use ($validated): array {
            $currentRefreshToken = $this->backofficeRefreshTokenService->consume(
                $validated[RefreshBackofficeTokenRequest::REFRESH_TOKEN],
            );

            if (! $currentRefreshToken instanceof BackofficeRefreshToken) {
                throw ValidationException::withMessages([
                    RefreshBackofficeTokenRequest::REFRESH_TOKEN => 'Invalid refresh token.',
                ]);
            }

            /** @var BackofficeAgent $agent */
            $agent = $currentRefreshToken->agent()->firstOrFail();
            $absoluteExpiresAt = $currentRefreshToken->getAttribute(BackofficeRefreshToken::ABSOLUTE_EXPIRES_AT);

            [, $newRefreshToken] = $this->backofficeRefreshTokenService->issue(
                $agent,
                now(),
                $absoluteExpiresAt,
            );

            return [$this->issueAccessToken($agent), $newRefreshToken];
        });

        return new BackofficeAuthTokensDTO($accessToken, $newRefreshToken);
    }

    private function issueAccessToken(BackofficeAgent $agent): string
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('backoffice');

        return $guard
            ->setTTL(config('auth.backoffice.access_token_ttl'))
            ->login($agent);
    }
}
