<?php

namespace App\Domain\Backoffice\Actions;

use App\Domain\Backoffice\Services\BackofficeRefreshTokenService;
use App\Http\Requests\Api\Backoffice\LogoutBackofficeSessionRequest;

class LogoutBackofficeSessionAction
{
    public function __construct(
        private readonly BackofficeRefreshTokenService $backofficeRefreshTokenService,
    ) {
    }

    /**
     * @param array{refresh_token: string} $validated
     */
    public function __invoke(array $validated): void
    {
        $this->backofficeRefreshTokenService->consume(
            $validated[LogoutBackofficeSessionRequest::REFRESH_TOKEN],
        );
    }
}
