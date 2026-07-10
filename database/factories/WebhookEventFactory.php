<?php

namespace Database\Factories;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    public function definition(): array
    {
        return [
            'platform' => 'page',
            'object_type' => 'page',
            'object_id' => (string) fake()->randomNumber(9),
            'signature_valid' => true,
            'raw_payload' => ['object' => 'page', 'entry' => []],
            'received_at' => now(),
            'processed_at' => null,
            'status' => WebhookEventStatus::Received,
        ];
    }

    public function processed(): static
    {
        return $this->state(fn () => [
            'status' => WebhookEventStatus::Processed,
            'processed_at' => now(),
        ]);
    }
}
