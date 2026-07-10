<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Read-only inspector over the raw webhook_events store, for debugging and
 * replay analysis (BARIQ §6.8). No mutation — replay would re-dispatch
 * ProcessMetaWebhook and is a deliberate seam left for future need.
 */
class WebhookEventController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless((bool) $request->user()?->can('inspect-webhooks'), 403);

        $events = QueryBuilder::for(WebhookEvent::class)
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::exact('platform'),
            )
            ->allowedSorts('received_at')
            ->defaultSort('-received_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/webhook-events/index', ['events' => $events]);
    }

    public function show(Request $request, WebhookEvent $webhookEvent): Response
    {
        abort_unless((bool) $request->user()?->can('inspect-webhooks'), 403);

        return Inertia::render('admin/webhook-events/show', ['event' => $webhookEvent]);
    }
}
