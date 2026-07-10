<?php

namespace App\Jobs;

use App\Enums\WebhookEventStatus;
use App\Models\WebhookEvent;
use App\Services\Automation\ReplyDispatcher;
use App\Services\Automation\RuleMatcher;
use App\Services\Billing\SubscriptionQuota;
use App\Services\Meta\ChannelConnectionResolver;
use App\Services\Meta\MetaWebhookParser;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Processes one stored webhook event: normalizes it, resolves the owning tenant
 * from the Meta asset id, and (from Phase 5) matches rules + dispatches replies.
 *
 * CRITICAL (BARIQ §3): there is NO authenticated user here. The tenant is
 * resolved from the asset id and set on the container manually before any
 * tenant-scoped model is touched.
 */
class ProcessMetaWebhook implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $webhookEventId) {}

    public function handle(
        MetaWebhookParser $parser,
        ChannelConnectionResolver $resolver,
        TenantContext $tenantContext,
        SubscriptionQuota $quota,
        PermissionRegistrar $permissions,
        RuleMatcher $ruleMatcher,
        ReplyDispatcher $replyDispatcher,
    ): void {
        $event = WebhookEvent::find($this->webhookEventId);

        // Idempotency: a stored event is processed at most once.
        if ($event === null || $event->isProcessed()) {
            return;
        }

        try {
            $incoming = $parser->parse($event->raw_payload);
            $handledAny = false;

            foreach ($incoming as $item) {
                $connection = $resolver->forAsset($item->assetId);

                // Unknown asset, or a connection that isn't active → ignore.
                if ($connection === null || ! $connection->isActive()) {
                    continue;
                }

                // Skip self-authored events (the page/account acting on itself) to avoid loops.
                if ($item->actorId !== null && $item->actorId === $connection->provider_account_id) {
                    continue;
                }

                $tenant = $connection->tenant;
                if ($tenant === null) {
                    continue;
                }

                // Bind the resolved tenant before touching any tenant-scoped model.
                $tenantContext->set($tenant);
                $permissions->setPermissionsTeamId($tenant->id);

                // Automation is paused for suspended tenants (admin kill switch)
                // and for tenants without an active subscription.
                if (! $tenant->isActive() || ! $quota->hasActiveSubscription($tenant)) {
                    continue;
                }

                // Match a rule and queue its replies (idempotent via reply_logs).
                $rule = $ruleMatcher->match($connection, $item->surface, $item->parentId, $item->text);

                if ($rule !== null) {
                    $replyDispatcher->dispatch($connection, $rule, $item);
                }

                $handledAny = true;
            }

            $event->markProcessed($handledAny ? WebhookEventStatus::Processed : WebhookEventStatus::Skipped);
        } catch (Throwable $e) {
            $event->update(['status' => WebhookEventStatus::Failed]);

            throw $e;
        } finally {
            $tenantContext->forget();
        }
    }
}
