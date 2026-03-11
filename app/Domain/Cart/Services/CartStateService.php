<?php

namespace App\Domain\Cart\Services;

use App\Domain\Cart\DTOs\CartViewDTO;
use App\Domain\Cart\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CartStateService
{
    public function __construct(
        private readonly CartStorageService $cartStorageService,
    ) {
    }

    /**
     * @return array{?string, array<int, array{product_id: int, quantity: int}>}
     */
    public function loadTokenAndLines(Request $request, string $siteCode): array
    {
        $token = $this->resolveTokenFromRequest($request);
        if ($token === null) {
            return [null, []];
        }

        $payload = $this->cartStorageService->find($siteCode, $token);

        if ($payload === null) {
            return [null, []];
        }

        return [$token, $this->normalizeLines($payload['lines'] ?? [])];
    }

    public function resolveTokenFromRequest(Request $request): ?string
    {
        $tokenHeader = (string) config('cart.token_header');
        $providedToken = $request->headers->get($tokenHeader);

        if (! is_string($providedToken) || $providedToken === '') {
            return null;
        }

        return $providedToken;
    }

    /**
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public function loadLines(string $siteCode, ?string $token): array
    {
        if ($token === null) {
            return [];
        }

        $payload = $this->cartStorageService->find($siteCode, $token);

        if ($payload === null) {
            return [];
        }

        return $this->normalizeLines($payload['lines'] ?? []);
    }

    /**
     * @param mixed $lines
     * @return array<int, array{product_id: int, quantity: int}>
     */
    public function normalizeLines(mixed $lines): array
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
     */
    public function assertQuantityWithinStock(int $productId, array $storedLines): void
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
                throw InsufficientStockException::forRequestedQuantity();
            }

            return;
        }
    }

    /**
     * @param array<int, array{product_id: int, quantity: int}> $storedLines
     */
    public function buildView(string $token, int $siteId, array $storedLines): CartViewDTO
    {
        $quantitiesByProductId = [];

        foreach ($storedLines as $line) {
            $quantitiesByProductId[$line['product_id']] = $line['quantity'];
        }

        if ($quantitiesByProductId === []) {
            return CartViewDTO::empty($token);
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
                'product_site_prices.'.ProductSitePrice::PRICE_AMOUNT_CENTS.' as unit_price_amount_cents',
            ])
            ->keyBy('product_id');

        if ($pricedProducts->count() !== count($quantitiesByProductId)) {
            throw new NotFoundHttpException();
        }

        $lines = [];
        $totalAmountCents = 0;

        foreach ($quantitiesByProductId as $resolvedProductId => $quantity) {
            $pricedProduct = $pricedProducts->get($resolvedProductId);

            if (! $pricedProduct instanceof Product) {
                throw new NotFoundHttpException();
            }

            $unitPriceAmountCents = (int) $pricedProduct->getAttribute('unit_price_amount_cents');
            $lineTotalAmountCents = $unitPriceAmountCents * $quantity;

            $lines[] = [
                'product_id' => (int) $pricedProduct->getAttribute('product_id'),
                'name' => (string) $pricedProduct->getAttribute(Product::NAME),
                'quantity' => $quantity,
                'unit_price_amount_cents' => $unitPriceAmountCents,
                'line_total_amount_cents' => $lineTotalAmountCents,
            ];

            $totalAmountCents += $lineTotalAmountCents;
        }

        return new CartViewDTO(
            lines: $lines,
            itemCount: count($lines),
            totalAmountCents: $totalAmountCents,
            token: $token,
        );
    }
}
