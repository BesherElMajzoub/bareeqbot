<?php

namespace App\Models;

use App\Enums\RuleActionType;
use Database\Factories\RuleActionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single ordered step of an automation rule. Not tenant-scoped directly —
 * it is always reached through its (tenant-scoped) AutomationRule.
 *
 * @property int $id
 * @property int $rule_id
 * @property RuleActionType $action_type
 * @property string $message_template
 * @property int $delay_seconds
 * @property int $sort
 */
#[Fillable(['rule_id', 'action_type', 'message_template', 'delay_seconds', 'sort'])]
class RuleAction extends Model
{
    /** @use HasFactory<RuleActionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'action_type' => RuleActionType::class,
            'delay_seconds' => 'integer',
            'sort' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AutomationRule, $this>
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }
}
