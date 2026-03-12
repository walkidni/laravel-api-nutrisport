<?php

namespace App\Domain\Backoffice\Actions;

use App\Domain\Backoffice\Models\BackofficeAgent;
use App\Domain\Backoffice\Support\BackofficeAuthorization;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Support\Facades\DB;

final class CreateBackofficeProductAction
{
    public function __construct(
        private readonly BackofficeAuthorization $backofficeAuthorization,
    ) {
    }

    /**
     * @param array{
     *   name: string,
     *   initial_stock: int,
     *   site_prices: array<int, array{site_code: string, price: string}>
     * } $validated
     */
    public function __invoke(BackofficeAgent $agent, array $validated): Product
    {
        $this->backofficeAuthorization->ensureCanCreateProducts($agent);

        /** @var Product $product */
        $product = DB::transaction(function () use ($validated): Product {
            $product = Product::query()->create([
                Product::NAME => $validated[Product::NAME],
                Product::STOCK => $validated['initial_stock'],
            ]);

            $sitePrices = collect($validated['site_prices']);
            $sitesByCode = Site::query()
                ->whereIn(Site::CODE, $sitePrices->pluck('site_code')->all())
                ->get()
                ->keyBy(Site::CODE);

            foreach ($validated['site_prices'] as $sitePrice) {
                /** @var Site $site */
                $site = $sitesByCode->get($sitePrice['site_code']);

                ProductSitePrice::query()->create([
                    ProductSitePrice::PRODUCT_ID => (int) $product->getKey(),
                    ProductSitePrice::SITE_ID => (int) $site->getKey(),
                    ProductSitePrice::PRICE_AMOUNT_CENTS => $this->parsePriceToCents($sitePrice['price']),
                ]);
            }

            return $product->load([
                'sitePrices.site' => fn ($query) => $query->orderBy(Site::CODE),
            ]);
        });

        return $product;
    }

    private function parsePriceToCents(string $price): int
    {
        [$whole, $fractional] = array_pad(explode('.', $price, 2), 2, '0');
        $fractional = str_pad(substr($fractional, 0, 2), 2, '0');

        return ((int) $whole * 100) + (int) $fractional;
    }
}
