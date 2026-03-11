<?php

namespace App\Http\Controllers\Api\CustomerAuth;

use App\Domain\Customers\Actions\RegisterCustomerAction;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerAuth\RegisterCustomerRequest;
use App\Http\Resources\Api\CustomerAuth\RegisteredCustomerResource;
use Illuminate\Http\JsonResponse;

class CustomerAuthController extends Controller
{
    public function __construct(
        private readonly CurrentSiteContextService $currentSiteContextService,
    ) {
    }

    public function register(
        RegisterCustomerRequest $request,
        RegisterCustomerAction $registerCustomerAction,
    ): JsonResponse {
        $customer = $registerCustomerAction(
            $this->currentSiteContextService->get($request),
            $request->validated(),
        );

        return RegisteredCustomerResource::make($customer)
            ->response()
            ->setStatusCode(201);
    }
}
