<?php

namespace Database\Factories;

use App\Enums\SubscriptionRequestStatus;
use App\Models\PlanPrice;
use App\Models\SubscriptionRequest;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionRequest>
 */
class SubscriptionRequestFactory extends Factory
{
    protected $model = SubscriptionRequest::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'plan_price_id' => PlanPrice::factory(),
            'payment_proof_path' => null,
            'payer_note' => fake()->optional()->sentence(),
            'status' => SubscriptionRequestStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionRequestStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionRequestStatus::Rejected,
            'reviewed_at' => now(),
            'reject_reason' => fake()->sentence(),
        ]);
    }
}
