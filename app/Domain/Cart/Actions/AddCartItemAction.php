<?php

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Cart\Services\CartStateService;
use App\Domain\Cart\Services\CartStorageService;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AddCartItemAction
{
    public function __construct(
        private readonly CartStorageService $cartStorageService,
        private readonly CartStateService $cartStateService,
    ) {
    }

    /**
     * @param array{product_id: int, quantity: int} $validated
     */
    public function __invoke(Request $request, Site $site, array $validated): CartViewDTO
    {
        $siteCode = (string) $site->getAttribute(Site::CODE);
        $siteId = (int) $site->getKey();
        $requestedProductId = (int) $validated['product_id'];
        $requestedQuantity = (int) $validated['quantity'];

        [$token, $storedLines] = $this->cartStateService->loadTokenAndLines($request, $siteCode);
        $token ??= $this->generateToken();
        $updatedLines = $this->incrementLineQuantity($storedLines, $requestedProductId, $requestedQuantity);
        $this->cartStateService->assertQuantityWithinStock($requestedProductId, $updatedLines);
        $cartView = $this->cartStateService->buildView($token, $siteId, $updatedLines);

        $this->cartStorageService->put($siteCode, $token, [
            'lines' => $updatedLines,
        ]);

        return $cartView;
    }

    /**
     * @param array<int, array{product_id: int, quantity: int}> $storedLines
     * @return array<int, array{product_id: int, quantity: int}>
     */
    private function incrementLineQuantity(array $storedLines, int $productId, int $quantity): array
    {
        foreach ($storedLines as $index => $line) {
            if ($line['product_id'] !== $productId) {
                continue;
            }

            $storedLines[$index]['quantity'] += $quantity;

            return $storedLines;
        }

        $storedLines[] = [
            'product_id' => $productId,
            'quantity' => $quantity,
        ];

        return $storedLines;
    }

    private function generateToken(): string
    {
        return Str::random(40);
    }
}
