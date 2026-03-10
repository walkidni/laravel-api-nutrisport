<?php

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Cart\Services\CartStorageService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;

class ShowCartAction
{
    public function __construct(
        private readonly CartStorageService $cartStorageService,
    ) {
    }

    public function __invoke(Request $request, Site $site): CartViewDTO
    {
        $tokenHeader = (string) config('cart.token_header');
        $token = $request->headers->get($tokenHeader);

        if (! is_string($token) || $token === '') {
            return CartViewDTO::empty();
        }

        $siteCode = (string) $site->getAttribute(Site::CODE);
        $payload = $this->cartStorageService->find($siteCode, $token);

        if ($payload === null) {
            return CartViewDTO::empty();
        }

        $lines = $payload['lines'] ?? [];

        if (! is_array($lines)) {
            $lines = [];
        }

        return new CartViewDTO(
            lines: $lines,
            itemCount: count($lines),
            totalAmount: 0,
            token: $token,
        );
    }
}
