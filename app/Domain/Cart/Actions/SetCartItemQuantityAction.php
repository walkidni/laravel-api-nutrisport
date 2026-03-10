<?php

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Cart\Services\CartStateService;
use App\Domain\Cart\Services\CartStorageService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;

class SetCartItemQuantityAction
{
    public function __construct(
        private readonly CartStorageService $cartStorageService,
        private readonly CartStateService $cartStateService,
    ) {
    }

    /**
     * @param array{quantity: int} $validated
     */
    public function __invoke(Request $request, Site $site, int $productId, array $validated): CartViewDTO
    {
        $siteCode = (string) $site->getAttribute(Site::CODE);
        $siteId = (int) $site->getKey();
        $quantity = (int) $validated['quantity'];
        [$token, $storedLines] = $this->cartStateService->loadTokenAndLines($request, $siteCode);

        if ($token === null) {
            return CartViewDTO::empty();
        }
        [$updatedLines, $lineWasFound] = $this->setLineQuantity($storedLines, $productId, $quantity);

        if (! $lineWasFound) {
            return $this->cartStateService->buildView($token, $siteId, $storedLines);
        }

        $this->cartStateService->assertQuantityWithinStock($productId, $updatedLines);

        if ($updatedLines === []) {
            $this->cartStorageService->forget($siteCode, $token);

            return CartViewDTO::empty($token);
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
    private function setLineQuantity(array $storedLines, int $productId, int $quantity): array
    {
        foreach ($storedLines as $index => $line) {
            if ($line['product_id'] !== $productId) {
                continue;
            }

            if ($quantity === 0) {
                unset($storedLines[$index]);

                return [array_values($storedLines), true];
            }

            $storedLines[$index]['quantity'] = $quantity;

            return [$storedLines, true];
        }

        return [$storedLines, false];
    }
}
