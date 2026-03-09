<?php

namespace Database\Factories;

use App\Domain\Shared\SiteContext\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $code = $this->faker->randomElement(['fr', 'it', 'be']);

        return [
            Site::CODE => $code,
            Site::DOMAIN => "nutri-sport.{$code}",
        ];
    }
}
