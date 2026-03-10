<?php

namespace App\Http\Controllers\Api\Cart;

use App\Domain\Cart\Actions\ShowCartAction;
use App\Domain\Shared\SiteContext\CurrentSiteContextService;
use App\Http\Controllers\Controller;
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

        $response = CartResource::make($cartView)->response();

        if ($cartView->token !== null) {
            $response->headers->set((string) config('cart.token_header'), $cartView->token);
        }

        return $response;
    }
}
