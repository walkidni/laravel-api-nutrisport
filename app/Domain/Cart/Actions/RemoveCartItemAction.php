<?php

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Cart\Services\CartStateService;
use App\Domain\Cart\Services\CartStorageService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;

class RemoveCartItemAction
{
    public function __construct(
        private readonly CartStorageService $cartStorageService,
        private readonly CartStateService $cartStateService,
    ) {
    }

    public function __invoke(Request $request, Site $site, int $productId): CartViewDTO
    {
        $siteCode = (string) $site->getAttribute(Site::CODE);
        $siteId = (int) $site->getKey();
        [$token, $storedLines] = $this->cartStateService->loadTokenAndLines($request, $siteCode);

        if ($token === null) {
            return CartViewDTO::empty();
        }

        [$updatedLines, $lineWasFound] = $this->removeLine($storedLines, $productId);

        if (! $lineWasFound) {
            return $this->cartStateService->buildView($token, $siteId, $storedLines);
        }

        if ($updatedLines === []) {
            $this->cartStorageService->forget($siteCode, $token);

            return CartViewDTO::empty();
        }

        $cartView = $this->cartStateService->buildView($token, $siteId, $updatedLines);

        $this->cartStorageService->put($siteCode, $token, [
            'lines' => $updatedLines,
        ]);

        return $cartView;
    }

    /**
     * @param array<int, array{product_id: int, quantity: int}> $storedLines
     * @return array{array<int, array{product_id: int, quantity: int}>, bool}
     */
    private function removeLine(array $storedLines, int $productId): array
    {
        foreach ($storedLines as $index => $line) {
            if ($line['product_id'] !== $productId) {
                continue;
            }

            unset($storedLines[$index]);

            return [array_values($storedLines), true];
        }

        return [$storedLines, false];
    }
}
