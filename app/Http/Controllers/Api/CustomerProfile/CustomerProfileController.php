<?php

namespace App\Http\Controllers\Api\CustomerProfile;

use App\Domain\Customers\Actions\ShowCustomerProfileAction;
use App\Domain\Customers\Actions\UpdateCustomerPasswordAction;
use App\Domain\Customers\Actions\UpdateCustomerProfileAction;
use App\Domain\Customers\Services\CurrentCustomerContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerProfile\UpdateCustomerPasswordRequest;
use App\Http\Requests\Api\CustomerProfile\UpdateCustomerProfileRequest;
use App\Http\Resources\Api\CustomerProfile\CustomerProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerProfileController extends Controller
{
    public function __construct(
        private readonly CurrentCustomerContextService $currentCustomerContextService,
    ) {
    }

    public function show(
        Request $request,
        ShowCustomerProfileAction $showCustomerProfileAction,
    ): JsonResponse {
        return CustomerProfileResource::make(
            $showCustomerProfileAction($this->currentCustomerContextService->getForResolvedSite($request)),
        )->response()->setStatusCode(200);
    }

    public function update(
        UpdateCustomerProfileRequest $request,
        UpdateCustomerProfileAction $updateCustomerProfileAction,
    ): JsonResponse {
        return CustomerProfileResource::make(
            $updateCustomerProfileAction(
                $this->currentCustomerContextService->getForResolvedSite($request),
                $request->validated(),
            ),
        )->response()->setStatusCode(200);
    }

    public function updatePassword(
        UpdateCustomerPasswordRequest $request,
        UpdateCustomerPasswordAction $updateCustomerPasswordAction,
    ): Response {
        $updateCustomerPasswordAction(
            $this->currentCustomerContextService->getForResolvedSite($request),
            $request->validated(),
        );

        return response()->noContent();
    }
}
