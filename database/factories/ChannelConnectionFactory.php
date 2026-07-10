<?php

namespace Database\Factories;

use App\Enums\ChannelPlatform;
use App\Enums\ChannelStatus;
use App\Models\ChannelConnection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelConnection>
 */
class ChannelConnectionFactory extends Factory
{
    protected $model = ChannelConnection::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'platform' => ChannelPlatform::Facebook,
            'provider_account_id' => (string) fake()->unique()->numerify('##########'),
            'linked_page_id' => null,
            'name' => fake()->company(),
            'username' => null,
            'access_token' => fake()->sha256(),
            'token_expires_at' => now()->addDays(60),
            'webhook_subscribed' => true,
            'status' => ChannelStatus::Active,
            'meta' => null,
            'connected_by' => User::factory(),
        ];
    }

    /**
     * Facebook page connection state.
     */
    public function facebook(): static
    {
        return $this->state(fn () => [
            'platform' => ChannelPlatform::Facebook,
            'username' => null,
        ]);
    }

    /**
     * Instagram business account connection state.
     */
    public function instagram(): static
    {
        return $this->state(fn () => [
            'platform' => ChannelPlatform::Instagram,
            'username' => fake()->userName(),
            'linked_page_id' => (string) fake()->numerify('##########'),
        ]);
    }

    /**
     * Revoked connection state.
     */
    public function revoked(): static
    {
        return $this->state(fn () => [
            'status' => ChannelStatus::Revoked,
            'webhook_subscribed' => false,
        ]);
    }

    /**
     * Error (token expired / revoked mid-session) state.
     */
    public function error(): static
    {
        return $this->state(fn () => [
            'status' => ChannelStatus::Error,
        ]);
    }
}
