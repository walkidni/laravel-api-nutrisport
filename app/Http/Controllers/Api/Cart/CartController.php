<?php

namespace App\Http\Controllers\Api\Cart;

use App\Domain\Cart\Actions\AddCartItemAction;
use App\Domain\Cart\Actions\SetCartItemQuantityAction;
use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Cart\Exceptions\InsufficientStock;
use App\Domain\Cart\Actions\ShowCartAction;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cart\AddCartItemRequest;
use App\Http\Requests\Api\Cart\SetCartItemQuantityRequest;
use App\Http\Resources\Api\Cart\CartResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CurrentSiteContextService $currentSiteContextService,
    ) {
    }

    public function show(Request $request, ShowCartAction $showCartAction): JsonResponse
    {
        $cartView = $showCartAction($request, $this->currentSiteContextService->get($request));

        return $this->respond($cartView);
    }

    public function addItem(
        AddCartItemRequest $request,
        AddCartItemAction $addCartItemAction,
    ): JsonResponse {
        return $this->handleCartMutation(function () use ($request, $addCartItemAction): CartViewDTO {
            return $addCartItemAction(
                $request,
                $this->currentSiteContextService->get($request),
                $request->validated(),
            );
        });
    }

    public function setItemQuantity(
        SetCartItemQuantityRequest $request,
        int $product,
        SetCartItemQuantityAction $setCartItemQuantityAction,
    ): JsonResponse {
        return $this->handleCartMutation(function () use ($request, $product, $setCartItemQuantityAction): CartViewDTO {
            return $setCartItemQuantityAction(
                $request,
                $this->currentSiteContextService->get($request),
                $product,
                $request->validated(),
            );
        });
    }

    private function handleCartMutation(callable $callback): JsonResponse
    {
        try {
            $cartView = $callback();
        } catch (InsufficientStock $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return $this->respond($cartView);
    }

    private function respond(CartViewDTO $cartView): JsonResponse
    {
        $response = CartResource::make($cartView)->response();

        if ($cartView->token !== null) {
            $response->headers->set((string) config('cart.token_header'), $cartView->token);
        }

        return $response;
    }
}
