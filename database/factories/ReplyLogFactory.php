<?php

namespace Database\Factories;

use App\Enums\ChannelPlatform;
use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Enums\WebhookSurface;
use App\Models\ChannelConnection;
use App\Models\ReplyLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReplyLog>
 */
class ReplyLogFactory extends Factory
{
    protected $model = ReplyLog::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'channel_connection_id' => ChannelConnection::factory(),
            'rule_id' => null,
            'platform' => ChannelPlatform::Facebook,
            'surface' => WebhookSurface::PostComment,
            'source_object_id' => (string) fake()->unique()->numerify('##########'),
            'actor_id' => (string) fake()->numerify('##########'),
            'action_type' => RuleActionType::PublicReply,
            'status' => ReplyLogStatus::Sent,
            'responded_at' => now(),
        ];
    }
}
