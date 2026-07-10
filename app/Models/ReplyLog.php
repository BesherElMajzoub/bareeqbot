<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\ChannelPlatform;
use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Enums\WebhookSurface;
use Database\Factories\ReplyLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Audit + idempotency record for every reply attempt. The unique index on
 * (platform, source_object_id, action_type) is the primary guard that a given
 * object is never replied to twice with the same action.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_connection_id
 * @property int|null $rule_id
 * @property ChannelPlatform $platform
 * @property WebhookSurface $surface
 * @property string $source_object_id
 * @property string|null $actor_id
 * @property string|null $parent_ref
 * @property RuleActionType $action_type
 * @property ReplyLogStatus $status
 * @property string|null $error
 * @property Carbon|null $responded_at
 */
#[Fillable([
    'tenant_id',
    'channel_connection_id',
    'rule_id',
    'platform',
    'surface',
    'source_object_id',
    'actor_id',
    'parent_ref',
    'action_type',
    'status',
    'error',
    'responded_at',
])]
class ReplyLog extends Model
{
    /** @use HasFactory<ReplyLogFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'platform' => ChannelPlatform::class,
            'surface' => WebhookSurface::class,
            'action_type' => RuleActionType::class,
            'status' => ReplyLogStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ChannelConnection, $this>
     */
    public function channelConnection(): BelongsTo
    {
        return $this->belongsTo(ChannelConnection::class);
    }

    /**
     * @return BelongsTo<AutomationRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }
}
