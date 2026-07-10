<?php

namespace App\Console\Commands;

use App\Models\DailyStat;
use App\Models\ReplyLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Rolls up a day's reply_logs into daily_stats per tenant + connection. Safe to
 * re-run: existing rows for the date are replaced.
 */
class AggregateStats extends Command
{
    protected $signature = 'stats:aggregate {date? : Date to aggregate as Y-m-d (defaults to yesterday)}';

    protected $description = 'Aggregate reply_logs into daily_stats per tenant + connection.';

    public function handle(): int
    {
        $dateArg = $this->argument('date');
        $date = is_string($dateArg) ? Carbon::parse($dateArg) : Carbon::yesterday();

        $rows = ReplyLog::withoutTenantScope()
            ->selectRaw('tenant_id, channel_connection_id')
            ->selectRaw('COUNT(DISTINCT source_object_id) as events_received')
            ->selectRaw("SUM(CASE WHEN action_type IN ('public_reply','private_reply') AND status='sent' THEN 1 ELSE 0 END) as replies_sent")
            ->selectRaw("SUM(CASE WHEN action_type='dm' AND status='sent' THEN 1 ELSE 0 END) as dms_sent")
            ->selectRaw("SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failures")
            ->whereBetween('created_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
            ->groupBy('tenant_id', 'channel_connection_id')
            ->toBase()
            ->get();

        DailyStat::withoutTenantScope()->whereDate('date', $date->toDateString())->delete();

        foreach ($rows as $row) {
            DailyStat::create([
                'tenant_id' => $row->tenant_id,
                'channel_connection_id' => $row->channel_connection_id,
                'date' => $date->toDateString(),
                'events_received' => (int) $row->events_received,
                'replies_sent' => (int) $row->replies_sent,
                'dms_sent' => (int) $row->dms_sent,
                'failures' => (int) $row->failures,
            ]);
        }

        $this->info("Aggregated {$rows->count()} row(s) for {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
