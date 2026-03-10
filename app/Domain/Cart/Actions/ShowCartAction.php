<?php

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Cart\Services\CartStateService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;

class ShowCartAction
{
    public function __construct(
        private readonly CartStateService $cartStateService,
    ) {
    }

    public function __invoke(Request $request, Site $site): CartViewDTO
    {
        $siteCode = (string) $site->getAttribute(Site::CODE);
        $siteId = (int) $site->getKey();
        [$token, $storedLines] = $this->cartStateService->loadTokenAndLines($request, $siteCode);

        if ($token === null) {
            return CartViewDTO::empty();
        }

        return $this->cartStateService->buildView($token, $siteId, $storedLines);
    }
}
