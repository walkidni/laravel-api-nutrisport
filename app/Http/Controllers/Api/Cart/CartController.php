<?php

namespace App\Http\Controllers\Api\Cart;

use App\Domain\Cart\Actions\ShowCartAction;
use App\Domain\Shared\SiteContext\CurrentSiteResolver;
use App\Domain\Shared\SiteContext\Site;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Cart\CartResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartController extends Controller
{
    public function show(Request $request, ShowCartAction $showCartAction): JsonResponse
    {
        $cartView = $showCartAction($request, $this->currentSite($request));

        $response = CartResource::make($cartView)->response();

        if ($cartView->token !== null) {
            $response->headers->set((string) config('cart.token_header'), $cartView->token);
        }

        return $response;
    }

    private function currentSite(Request $request): Site
    {
        $site = $request->attributes->get(CurrentSiteResolver::REQUEST_ATTRIBUTE);

        if ($site instanceof Site) {
            return $site;
        }

        throw new NotFoundHttpException();
    }
}
