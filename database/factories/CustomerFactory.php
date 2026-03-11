<?php

namespace Database\Factories;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            Customer::SITE_ID => Site::factory(),
            Customer::FIRST_NAME => $this->faker->firstName(),
            Customer::LAST_NAME => $this->faker->lastName(),
            Customer::EMAIL => $this->faker->safeEmail(),
            Customer::PASSWORD => Hash::make('password'),
        ];
    }
}
