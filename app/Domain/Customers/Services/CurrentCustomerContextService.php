<?php

namespace App\Domain\Customers\Services;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class CurrentCustomerContextService
{
    public function __construct(
        private readonly CurrentSiteContextService $currentSiteContextService,
    ) {
    }

    public function get(): Customer
    {
        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer) {
            throw new AuthorizationException();
        }

        return $customer;
    }

    public function getForResolvedSite(Request $request): Customer
    {
        $site = $this->currentSiteContextService->get($request);
        $customer = $this->get();

        if ((int) $customer->getAttribute(Customer::SITE_ID) !== (int) $site->getKey()) {
            throw new AuthorizationException();
        }

        return $customer;
    }
}
