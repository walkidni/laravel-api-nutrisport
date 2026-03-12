<?php

namespace App\Domain\Backoffice\Actions;

use App\Domain\Backoffice\DTOs\BackofficeAuthTokensDTO;
use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Services\BackofficeRefreshTokenService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class LoginBackofficeAgentAction
{
    public function __construct(
        private readonly BackofficeRefreshTokenService $backofficeRefreshTokenService,
    ) {
    }

    /**
     * @param array{email: string, password: string} $validated
     */
    public function __invoke(array $validated): BackofficeAuthTokensDTO
    {
        $agent = BackofficeAgent::query()
            ->where(BackofficeAgent::EMAIL, $validated[BackofficeAgent::EMAIL])
            ->first();

        if (! $agent instanceof BackofficeAgent || ! Hash::check($validated[BackofficeAgent::PASSWORD], $agent->getAuthPassword())) {
            throw ValidationException::withMessages([
                BackofficeAgent::EMAIL => __('auth.failed'),
            ]);
        }

        /** @var JWTGuard $guard */
        $guard = Auth::guard('backoffice');

        $accessToken = $guard
            ->setTTL(config('auth.backoffice.access_token_ttl'))
            ->login($agent);

        [, $refreshToken] = $this->backofficeRefreshTokenService->issue($agent);

        return new BackofficeAuthTokensDTO($accessToken, $refreshToken);
    }
}
