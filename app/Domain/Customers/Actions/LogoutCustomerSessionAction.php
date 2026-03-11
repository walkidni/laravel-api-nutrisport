<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Services\CustomerRefreshTokenService;
use App\Domain\Shared\SiteContext\Site;
use App\Http\Requests\Api\CustomerAuth\LogoutCustomerSessionRequest;

class LogoutCustomerSessionAction
{
    public function __construct(
        private readonly CustomerRefreshTokenService $customerRefreshTokenService,
    ) {
    }

    /**
     * @param array{refresh_token: string} $validated
     */
    public function __invoke(Site $site, array $validated): void
    {
        $this->customerRefreshTokenService->consumeForSite(
            $validated[LogoutCustomerSessionRequest::REFRESH_TOKEN],
            (int) $site->getKey(),
        );
    }
}
