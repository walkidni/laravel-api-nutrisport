<?php

namespace App\Domain\Cart\Actions;

use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Cart\Exceptions\InsufficientStock;
use App\Domain\Cart\Services\CartStorageService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AddCartItemAction
{
    public function __construct(
        private readonly CartStorageService $cartStorageService,
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

        [$token, $storedLines] = $this->resolveCartState($request, $siteCode);
        $updatedLines = $this->incrementLineQuantity($storedLines, $requestedProductId, $requestedQuantity);
        $this->assertRequestedQuantityWithinStock($requestedProductId, $updatedLines);
        $cartView = $this->buildCartView($token, $siteId, $updatedLines);

        $this->cartStorageService->put($siteCode, $token, [
            'lines' => $updatedLines,
        ]);

        return $cartView;
    }

    /**
     * @return array{string, array<int, array{product_id: int, quantity: int}>}
     */
    private function resolveCartState(Request $request, string $siteCode): array
    {
        $tokenHeader = (string) config('cart.token_header');
        $providedToken = $request->headers->get($tokenHeader);

        if (is_string($providedToken) && $providedToken !== '') {
            $payload = $this->cartStorageService->find($siteCode, $providedToken);

            if ($payload !== null) {
                return [$providedToken, $this->normalizeStoredLines($payload['lines'] ?? [])];
            }
        }

        return [$this->generateToken(), []];
    }

    /**
     * @param mixed $lines
     * @return array<int, array{product_id: int, quantity: int}>
     */
    private function normalizeStoredLines(mixed $lines): array
    {
        if (! is_array($lines)) {
            return [];
        }

        $normalizedLines = [];

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $productId = $line['product_id'] ?? null;
            $quantity = $line['quantity'] ?? null;

            if (! is_int($productId) || ! is_int($quantity) || $quantity < 1) {
                continue;
            }

            $normalizedLines[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];
        }

        return $normalizedLines;
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

    /**
     * @param array<int, array{product_id: int, quantity: int}> $storedLines
     */
    private function assertRequestedQuantityWithinStock(int $productId, array $storedLines): void
    {
        $availableStock = Product::query()
            ->whereKey($productId)
            ->value(Product::STOCK);

        if (! is_int($availableStock)) {
            return;
        }

        foreach ($storedLines as $line) {
            if ($line['product_id'] !== $productId) {
                continue;
            }

            if ($line['quantity'] > $availableStock) {
                throw InsufficientStock::forRequestedQuantity();
            }

            return;
        }
    }

    /**
     * @param array<int, array{product_id: int, quantity: int}> $storedLines
     */
    private function buildCartView(string $token, int $siteId, array $storedLines): CartViewDTO
    {
        $quantitiesByProductId = [];

        foreach ($storedLines as $line) {
            $quantitiesByProductId[$line['product_id']] = $line['quantity'];
        }

        $pricedProducts = Product::query()
            ->join(
                'product_site_prices',
                'product_site_prices.product_id',
                '=',
                'products.id',
            )
            ->where('product_site_prices.site_id', $siteId)
            ->whereIn('products.id', array_keys($quantitiesByProductId))
            ->get([
                'products.id as product_id',
                'products.name',
                'product_site_prices.price_amount as unit_price_amount',
            ])
            ->keyBy('product_id');

        if ($pricedProducts->count() !== count($quantitiesByProductId)) {
            throw new NotFoundHttpException();
        }

        $lines = [];
        $totalAmount = 0;

        foreach ($quantitiesByProductId as $productId => $quantity) {
            $pricedProduct = $pricedProducts->get($productId);

            if (! $pricedProduct instanceof Product) {
                throw new NotFoundHttpException();
            }

            $unitPriceAmount = (int) $pricedProduct->getAttribute('unit_price_amount');
            $lineTotalAmount = $unitPriceAmount * $quantity;

            $lines[] = [
                'product_id' => (int) $pricedProduct->getAttribute('product_id'),
                'name' => (string) $pricedProduct->getAttribute(Product::NAME),
                'quantity' => $quantity,
                'unit_price_amount' => $unitPriceAmount,
                'line_total_amount' => $lineTotalAmount,
            ];

            $totalAmount += $lineTotalAmount;
        }

        return new CartViewDTO(
            lines: $lines,
            itemCount: count($lines),
            totalAmount: $totalAmount,
            token: $token,
        );
    }

    private function generateToken(): string
    {
        return Str::random(40);
    }
}
