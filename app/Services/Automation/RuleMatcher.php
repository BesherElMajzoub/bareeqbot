<?php

namespace App\Services\Automation;

use App\Enums\RuleMatchType;
use App\Enums\RuleTargetScope;
use App\Enums\WebhookSurface;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;

/**
 * Given an incoming event, returns the first matching active rule for a
 * connection + surface, evaluated by priority (higher first). Matching honors
 * target scope (all vs a specific post/media) and the keyword match type.
 */
class RuleMatcher
{
    public function match(
        ChannelConnection $connection,
        WebhookSurface $surface,
        ?string $targetRef,
        ?string $text,
    ): ?AutomationRule {
        return AutomationRule::query()
            ->where('channel_connection_id', $connection->id)
            ->where('trigger_surface', $surface)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->with('actions')
            ->get()
            ->first(fn (AutomationRule $rule): bool => $this->targetMatches($rule, $targetRef)
                && $this->textMatches($rule, $text));
    }

    protected function targetMatches(AutomationRule $rule, ?string $targetRef): bool
    {
        if ($rule->target_scope === RuleTargetScope::All) {
            return true;
        }

        return $targetRef !== null && $rule->target_ref === $targetRef;
    }

    protected function textMatches(AutomationRule $rule, ?string $text): bool
    {
        if ($rule->match_type === RuleMatchType::Any) {
            return true;
        }

        $keyword = (string) $rule->keyword;
        $subject = (string) $text;

        if ($rule->match_type === RuleMatchType::Regex) {
            $flags = $rule->case_sensitive ? '' : 'i';
            $pattern = '#'.str_replace('#', '\#', $keyword).'#'.$flags;

            return @preg_match($pattern, $subject) === 1;
        }

        if (! $rule->case_sensitive) {
            $keyword = mb_strtolower($keyword);
            $subject = mb_strtolower($subject);
        }

        if ($rule->match_type === RuleMatchType::Exact) {
            return $subject === $keyword;
        }

        // Contains — the only remaining match type.
        return $keyword !== '' && str_contains($subject, $keyword);
    }
}
