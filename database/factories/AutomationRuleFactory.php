<?php

namespace Database\Factories;

use App\Enums\RuleMatchType;
use App\Enums\RuleTargetScope;
use App\Enums\WebhookSurface;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRule>
 */
class AutomationRuleFactory extends Factory
{
    protected $model = AutomationRule::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'channel_connection_id' => ChannelConnection::factory(),
            'name' => fake()->words(2, true),
            'trigger_surface' => WebhookSurface::PostComment,
            'target_scope' => RuleTargetScope::All,
            'target_ref' => null,
            'match_type' => RuleMatchType::Any,
            'keyword' => null,
            'case_sensitive' => false,
            'priority' => 0,
            'is_active' => true,
        ];
    }

    public function contains(string $keyword): static
    {
        return $this->state(fn () => [
            'match_type' => RuleMatchType::Contains,
            'keyword' => $keyword,
        ]);
    }

    public function forPost(string $postId): static
    {
        return $this->state(fn () => [
            'target_scope' => RuleTargetScope::Specific,
            'target_ref' => $postId,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
