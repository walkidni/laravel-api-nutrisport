<?php

namespace App\Http\Controllers\Api\CustomerProfile;

use App\Domain\Customers\Actions\ShowCustomerProfileAction;
use App\Domain\Customers\Actions\UpdateCustomerPasswordAction;
use App\Domain\Customers\Actions\UpdateCustomerProfileAction;
use App\Domain\Customers\Queries\FindCustomerOrderQuery;
use App\Domain\Customers\Queries\ListCustomerOrdersQuery;
use App\Domain\Customers\Services\CurrentCustomerContextService;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerProfile\UpdateCustomerPasswordRequest;
use App\Http\Resources\Api\CustomerProfile\CustomerOrderDetailResource;
use App\Http\Requests\Api\CustomerProfile\UpdateCustomerProfileRequest;
use App\Http\Resources\Api\CustomerProfile\CustomerOrderSummaryResource;
use App\Http\Resources\Api\CustomerProfile\CustomerProfileResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

class CustomerProfileController extends Controller
{
    public function __construct(
        private readonly CurrentCustomerContextService $currentCustomerContextService,
        private readonly CurrentSiteContextService $currentSiteContextService,
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

    public function indexOrders(
        Request $request,
        ListCustomerOrdersQuery $listCustomerOrdersQuery,
    ): AnonymousResourceCollection {
        $customer = $this->currentCustomerContextService->getForResolvedSite($request);
        $site = $this->currentSiteContextService->get($request);

        return CustomerOrderSummaryResource::collection(
            $listCustomerOrdersQuery($customer, $site),
        );
    }

    public function showOrder(
        Request $request,
        int $order,
        FindCustomerOrderQuery $findCustomerOrderQuery,
    ): JsonResponse {
        $customer = $this->currentCustomerContextService->getForResolvedSite($request);
        $site = $this->currentSiteContextService->get($request);
        $foundOrder = $findCustomerOrderQuery($customer, $site, $order);

        if ($foundOrder === null) {
            throw new NotFoundHttpException();
        }

        return CustomerOrderDetailResource::make($foundOrder)
            ->response()
            ->setStatusCode(200);
    }
}
