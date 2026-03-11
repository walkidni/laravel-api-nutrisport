<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Actions\CheckoutAction;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Checkout\CheckoutRequest;
use App\Http\Resources\Api\Checkout\CheckoutResultResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CurrentSiteContextService $currentSiteContextService,
    ) {
    }

    public function store(
        CheckoutRequest $request,
        CheckoutAction $checkoutAction,
    ): JsonResponse {
        $site = $this->currentSiteContextService->get($request);

        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();

        if (! $customer instanceof Customer || (int) $customer->getAttribute(Customer::SITE_ID) !== (int) $site->getKey()) {
            throw new AuthorizationException();
        }

        $checkoutResult = $checkoutAction(
            $site,
            $customer,
            $request,
            $request->validated(),
        );

        return CheckoutResultResource::make($checkoutResult)
            ->response()
            ->setStatusCode(201);
    }
}
