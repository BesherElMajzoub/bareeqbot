<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanPrice>
 */
class PlanPriceFactory extends Factory
{
    protected $model = PlanPrice::class;

    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'duration_months' => fake()->randomElement([1, 3, 6, 12]),
            'price' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'SAR',
            'is_active' => true,
        ];
    }
}
