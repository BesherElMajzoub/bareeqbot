<?php

namespace Database\Factories;

use App\Enums\SubscriptionSource;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => Plan::factory(),
            'duration_months' => 1,
            'price' => 99,
            'currency' => 'SAR',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
            'status' => SubscriptionStatus::Active,
            'source' => SubscriptionSource::Manual,
            'created_by' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Expired,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function endingInThePast(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->subDay(),
        ]);
    }
}
