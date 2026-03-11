<?php

namespace App\Http\Controllers\Api\CustomerAuth;

use App\Domain\Customers\Actions\LoginCustomerAction;
use App\Domain\Customers\Actions\LogoutCustomerSessionAction;
use App\Domain\Customers\Actions\RefreshCustomerSessionAction;
use App\Domain\Customers\Actions\RegisterCustomerAction;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerAuth\LoginCustomerRequest;
use App\Http\Requests\Api\CustomerAuth\LogoutCustomerSessionRequest;
use App\Http\Requests\Api\CustomerAuth\RefreshCustomerTokenRequest;
use App\Http\Requests\Api\CustomerAuth\RegisterCustomerRequest;
use App\Http\Resources\Api\CustomerAuth\CustomerAuthTokensResource;
use App\Http\Resources\Api\CustomerAuth\RegisteredCustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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

    public function login(
        LoginCustomerRequest $request,
        LoginCustomerAction $loginCustomerAction,
    ): JsonResponse {
        $tokens = $loginCustomerAction(
            $this->currentSiteContextService->get($request),
            $request->validated(),
        );

        return CustomerAuthTokensResource::make($tokens)->response();
    }

    public function refresh(
        RefreshCustomerTokenRequest $request,
        RefreshCustomerSessionAction $refreshCustomerSessionAction,
    ): JsonResponse {
        $tokens = $refreshCustomerSessionAction(
            $this->currentSiteContextService->get($request),
            $request->validated(),
        );

        return CustomerAuthTokensResource::make($tokens)->response();
    }

    public function logout(
        LogoutCustomerSessionRequest $request,
        LogoutCustomerSessionAction $logoutCustomerSessionAction,
    ): Response {
        $logoutCustomerSessionAction(
            $this->currentSiteContextService->get($request),
            $request->validated(),
        );

        return response()->noContent();
    }
}
