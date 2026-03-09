<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductSitePrice;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSitePrice>
 */
class ProductSitePriceFactory extends Factory
{
    protected $model = ProductSitePrice::class;

    public function definition(): array
    {
        return [
            ProductSitePrice::PRODUCT_ID => Product::factory(),
            ProductSitePrice::SITE_ID => Site::factory(),
            ProductSitePrice::PRICE_AMOUNT => $this->faker->numberBetween(100, 20000),
        ];
    }
}
