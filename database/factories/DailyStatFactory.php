<?php

namespace Database\Factories;

use App\Models\DailyStat;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyStat>
 */
class DailyStatFactory extends Factory
{
    protected $model = DailyStat::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'channel_connection_id' => null,
            'date' => now()->toDateString(),
            'events_received' => fake()->numberBetween(0, 100),
            'replies_sent' => fake()->numberBetween(0, 80),
            'dms_sent' => fake()->numberBetween(0, 40),
            'failures' => fake()->numberBetween(0, 10),
        ];
    }
}
