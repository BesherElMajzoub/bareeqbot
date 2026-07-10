<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\RuleMatchType;
use App\Enums\RuleTargetScope;
use App\Enums\WebhookSurface;
use Database\Factories\AutomationRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $channel_connection_id
 * @property string $name
 * @property WebhookSurface $trigger_surface
 * @property RuleTargetScope $target_scope
 * @property string|null $target_ref
 * @property RuleMatchType $match_type
 * @property string|null $keyword
 * @property bool $case_sensitive
 * @property int $priority
 * @property bool $is_active
 */
#[Fillable([
    'tenant_id',
    'channel_connection_id',
    'name',
    'trigger_surface',
    'target_scope',
    'target_ref',
    'match_type',
    'keyword',
    'case_sensitive',
    'priority',
    'is_active',
])]
class AutomationRule extends Model
{
    /** @use HasFactory<AutomationRuleFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'trigger_surface' => WebhookSurface::class,
            'target_scope' => RuleTargetScope::class,
            'match_type' => RuleMatchType::class,
            'case_sensitive' => 'boolean',
            'priority' => 'integer',
            'is_active' => 'boolean',
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
     * @return HasMany<RuleAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(RuleAction::class, 'rule_id')->orderBy('sort');
    }
}
