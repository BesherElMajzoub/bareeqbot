<?php

namespace App\Actions\Automation;

use App\Models\AutomationRule;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates an automation rule together with its ordered actions.
 * Actions are fully replaced on update (simplest correct semantics for v1).
 */
class UpsertAutomationRule
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Tenant $tenant, array $data, ?AutomationRule $rule = null): AutomationRule
    {
        return DB::transaction(function () use ($tenant, $data, $rule): AutomationRule {
            $rule ??= new AutomationRule;

            $rule->fill([
                'channel_connection_id' => $data['channel_connection_id'],
                'name' => $data['name'],
                'trigger_surface' => $data['trigger_surface'],
                'target_scope' => $data['target_scope'],
                'target_ref' => $data['target_ref'] ?? null,
                'match_type' => $data['match_type'],
                'keyword' => $data['keyword'] ?? null,
                'case_sensitive' => $data['case_sensitive'] ?? false,
                'priority' => $data['priority'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);
            $rule->tenant_id = $tenant->id;
            $rule->save();

            $rule->actions()->delete();

            foreach (array_values($data['actions']) as $index => $action) {
                $rule->actions()->create([
                    'action_type' => $action['action_type'],
                    'message_template' => $action['message_template'],
                    'delay_seconds' => $action['delay_seconds'] ?? 0,
                    'sort' => $index,
                ]);
            }

            return $rule->load('actions');
        });
    }
}
