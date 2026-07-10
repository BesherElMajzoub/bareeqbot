<?php

namespace App\Http\Controllers;

use App\Models\ReplyLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AnalyticsLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless((bool) $request->user()?->can('view-analytics'), 403);

        // ReplyLog carries the tenant global scope, so results are tenant-scoped.
        $logs = QueryBuilder::for(ReplyLog::class)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('action_type'),
                AllowedFilter::exact('surface'),
                AllowedFilter::exact('channel_connection_id'),
            )
            ->allowedSorts('created_at')
            ->defaultSort('-created_at')
            ->with(['rule:id,name', 'channelConnection:id,name,platform'])
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('analytics/logs', ['logs' => $logs]);
    }
}
