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
        $rules = AutomationRule::query()
            ->where('channel_connection_id', $connection->id)
            ->where('trigger_surface', $surface)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->with('actions')
            ->get();

        if ($rules->isEmpty()) {
            \Illuminate\Support\Facades\Log::info('RuleMatcher: No active rules found for connection and surface', [
                'connection_id' => $connection->id,
                'surface' => $surface->value,
            ]);
            return null;
        }

        foreach ($rules as $rule) {
            $tMatch = $this->targetMatches($rule, $targetRef);
            $txtMatch = $this->textMatches($rule, $text);

            \Illuminate\Support\Facades\Log::info('RuleMatcher: Evaluated rule', [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'target_scope' => $rule->target_scope->value,
                'rule_target_ref' => $rule->target_ref,
                'incoming_target_ref' => $targetRef,
                'target_matches' => $tMatch,
                'text_matches' => $txtMatch,
            ]);

            if ($tMatch && $txtMatch) {
                return $rule;
            }
        }

        return null;
    }

    protected function targetMatches(AutomationRule $rule, ?string $targetRef): bool
    {
        if ($rule->target_scope === RuleTargetScope::All) {
            return true;
        }

        if ($targetRef === null || $rule->target_ref === null) {
            return false;
        }

        if ($rule->target_ref === $targetRef) {
            return true;
        }

        // Meta webhooks for FB post comments send post_id in PAGEID_POSTID format.
        // Match if target_ref equals the post ID part after underscore.
        if (str_contains($targetRef, '_')) {
            $parts = explode('_', $targetRef);
            $actualPostId = end($parts);

            if ($rule->target_ref === $actualPostId) {
                return true;
            }
        }

        if (str_contains((string) $rule->target_ref, '_')) {
            $parts = explode('_', (string) $rule->target_ref);
            $rulePostId = end($parts);

            if ($rulePostId === $targetRef) {
                return true;
            }
        }

        return false;
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
