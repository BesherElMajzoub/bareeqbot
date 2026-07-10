<?php

namespace App\Services\Analytics;

use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Models\ReplyLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Live automation analytics computed directly from reply_logs. Pass a tenant id
 * for a tenant dashboard, or null for platform-wide (admin) figures.
 */
class AnalyticsService
{
    /**
     * @return array{events_received:int, replies_sent:int, dms_sent:int, failures:int, success_rate:float}
     */
    public function summary(?int $tenantId, int $days = 30): array
    {
        $repliesSent = (clone $this->base($tenantId, $days))
            ->whereIn('action_type', [RuleActionType::PublicReply->value, RuleActionType::PrivateReply->value])
            ->where('status', ReplyLogStatus::Sent->value)
            ->count();

        $dmsSent = (clone $this->base($tenantId, $days))
            ->where('action_type', RuleActionType::Dm->value)
            ->where('status', ReplyLogStatus::Sent->value)
            ->count();

        $failures = (clone $this->base($tenantId, $days))
            ->where('status', ReplyLogStatus::Failed->value)
            ->count();

        $eventsReceived = (clone $this->base($tenantId, $days))
            ->distinct()
            ->count('source_object_id');

        $totalSent = $repliesSent + $dmsSent;
        $attempts = $totalSent + $failures;

        return [
            'events_received' => $eventsReceived,
            'replies_sent' => $repliesSent,
            'dms_sent' => $dmsSent,
            'failures' => $failures,
            'success_rate' => $attempts > 0 ? round($totalSent / $attempts * 100, 1) : 0.0,
        ];
    }

    /**
     * A zero-filled daily time series for charting.
     *
     * @return list<array{date:string, replies:int, dms:int, failures:int}>
     */
    public function dailySeries(?int $tenantId, int $days = 14): array
    {
        $rows = (clone $this->base($tenantId, $days))
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw("SUM(CASE WHEN action_type IN ('public_reply','private_reply') AND status='sent' THEN 1 ELSE 0 END) as replies")
            ->selectRaw("SUM(CASE WHEN action_type='dm' AND status='sent' THEN 1 ELSE 0 END) as dms")
            ->selectRaw("SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failures")
            ->groupBy('day')
            ->toBase()
            ->get()
            ->keyBy('day');

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $row = $rows->get($date);
            $series[] = [
                'date' => $date,
                'replies' => (int) ($row->replies ?? 0),
                'dms' => (int) ($row->dms ?? 0),
                'failures' => (int) ($row->failures ?? 0),
            ];
        }

        return $series;
    }

    /**
     * Rules ranked by successful replies.
     *
     * @return list<array{name:string, sent:int}>
     */
    public function topRules(?int $tenantId, int $days = 30, int $limit = 5): array
    {
        $rows = (clone $this->base($tenantId, $days))
            ->join('automation_rules', 'automation_rules.id', '=', 'reply_logs.rule_id')
            ->where('reply_logs.status', ReplyLogStatus::Sent->value)
            ->select('automation_rules.name as name')
            ->selectRaw('COUNT(*) as sent')
            ->groupBy('automation_rules.id', 'automation_rules.name')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->toBase()
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[] = ['name' => (string) $row->name, 'sent' => (int) $row->sent];
        }

        return $result;
    }

    /**
     * @return Builder<ReplyLog>
     */
    private function base(?int $tenantId, int $days): Builder
    {
        $query = ReplyLog::withoutTenantScope()
            ->where('reply_logs.created_at', '>=', now()->subDays($days)->startOfDay());

        if ($tenantId !== null) {
            $query->where('reply_logs.tenant_id', $tenantId);
        }

        return $query;
    }
}
