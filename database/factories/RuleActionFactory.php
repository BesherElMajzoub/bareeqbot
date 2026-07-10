<?php

namespace Database\Factories;

use App\Enums\RuleActionType;
use App\Models\AutomationRule;
use App\Models\RuleAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RuleAction>
 */
class RuleActionFactory extends Factory
{
    protected $model = RuleAction::class;

    public function definition(): array
    {
        return [
            'rule_id' => AutomationRule::factory(),
            'action_type' => RuleActionType::PublicReply,
            'message_template' => 'Hi {{commenter_name}}, thanks for your comment!',
            'delay_seconds' => 0,
            'sort' => 0,
        ];
    }

    public function publicReply(): static
    {
        return $this->state(fn () => ['action_type' => RuleActionType::PublicReply]);
    }

    public function privateReply(): static
    {
        return $this->state(fn () => ['action_type' => RuleActionType::PrivateReply]);
    }
}
