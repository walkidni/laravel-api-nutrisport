<?php

namespace Database\Factories;

use App\Domain\Backoffice\Models\BackofficeAgent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<BackofficeAgent>
 */
class BackofficeAgentFactory extends Factory
{
    protected $model = BackofficeAgent::class;

    public function definition(): array
    {
        return [
            BackofficeAgent::EMAIL => $this->faker->safeEmail(),
            BackofficeAgent::PASSWORD => Hash::make('password'),
            BackofficeAgent::CAN_VIEW_RECENT_ORDERS => false,
            BackofficeAgent::CAN_CREATE_PRODUCTS => false,
        ];
    }
}
