<?php

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\DTOs\CartView;
use App\Domain\Cart\Services\CartStorage;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;

class ShowCart
{
    public function __construct(
        private readonly CartStorage $cartStorage,
    ) {
    }

    public function __invoke(Request $request, Site $site): CartView
    {
        $tokenHeader = (string) config('cart.token_header');
        $token = $request->headers->get($tokenHeader);

        if (! is_string($token) || $token === '') {
            return CartView::empty();
        }

        $siteCode = (string) $site->getAttribute(Site::CODE);
        $payload = $this->cartStorage->find($siteCode, $token);

        if ($payload === null) {
            return CartView::empty();
        }

        $lines = $payload['lines'] ?? [];

        if (! is_array($lines)) {
            $lines = [];
        }

        return new CartView(
            lines: $lines,
            itemCount: count($lines),
            totalAmount: 0,
            token: $token,
        );
    }
}
