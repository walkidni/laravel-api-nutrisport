<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Domain\Orders\Actions\CheckoutAction;
use App\Domain\Orders\Exceptions\CheckoutException;
use App\Domain\Customers\Services\CurrentCustomerContextService;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Checkout\CheckoutRequest;
use App\Http\Resources\Api\Checkout\CheckoutResultResource;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CurrentCustomerContextService $currentCustomerContextService,
        private readonly CurrentSiteContextService $currentSiteContextService,
    ) {
    }

    public function store(
        CheckoutRequest $request,
        CheckoutAction $checkoutAction,
    ): JsonResponse {
        $customer = $this->currentCustomerContextService->getForResolvedSite($request);
        $site = $this->currentSiteContextService->get($request);

        try {
            $checkoutResult = $checkoutAction(
                $site,
                $customer,
                $request,
                $request->validated(),
            );
        } catch (CheckoutException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return CheckoutResultResource::make($checkoutResult)
            ->response()
            ->setStatusCode(201);
    }
}
