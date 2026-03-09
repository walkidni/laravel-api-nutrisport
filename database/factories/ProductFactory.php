<?php

namespace Database\Factories;

use App\Domain\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            Product::NAME => $this->faker->words(2, true),
            Product::STOCK => $this->faker->numberBetween(0, 1000),
        ];
    }
}
