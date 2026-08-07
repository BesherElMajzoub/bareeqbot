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

                if ($connection === null) {
                    \Illuminate\Support\Facades\Log::warning('ProcessMetaWebhook: Connection not found for asset ID', ['asset_id' => $item->assetId]);
                    continue;
                }

                if (! $connection->isActive()) {
                    \Illuminate\Support\Facades\Log::warning('ProcessMetaWebhook: Connection is inactive', ['connection_id' => $connection->id]);
                    continue;
                }

                // Skip self-authored events (the page/account acting on itself) to avoid loops.
                if ($item->actorId !== null && $item->actorId === $connection->provider_account_id) {
                    \Illuminate\Support\Facades\Log::info('ProcessMetaWebhook: Skipped self-authored comment by page/account', [
                        'actor_id' => $item->actorId,
                        'provider_account_id' => $connection->provider_account_id,
                    ]);
                    continue;
                }

                $tenant = $connection->tenant;
                if ($tenant === null) {
                    \Illuminate\Support\Facades\Log::warning('ProcessMetaWebhook: Tenant not found for connection', ['connection_id' => $connection->id]);
                    continue;
                }

                // Bind the resolved tenant before touching any tenant-scoped model.
                $tenantContext->set($tenant);
                $permissions->setPermissionsTeamId($tenant->id);

                // Automation is paused for suspended tenants (admin kill switch)
                // and for tenants without an active subscription.
                if (! $tenant->isActive()) {
                    \Illuminate\Support\Facades\Log::warning('ProcessMetaWebhook: Tenant is suspended/inactive', ['tenant_id' => $tenant->id]);
                    continue;
                }

                if (! $quota->hasActiveSubscription($tenant)) {
                    \Illuminate\Support\Facades\Log::warning('ProcessMetaWebhook: Tenant has no active subscription', ['tenant_id' => $tenant->id]);
                    continue;
                }

                \Illuminate\Support\Facades\Log::info('ProcessMetaWebhook: Attempting rule match', [
                    'connection_id' => $connection->id,
                    'surface' => $item->surface->value,
                    'parent_id' => $item->parentId,
                    'text' => $item->text,
                ]);

                // Match a rule and queue its replies (idempotent via reply_logs).
                $rule = $ruleMatcher->match($connection, $item->surface, $item->parentId, $item->text);

                if ($rule !== null) {
                    \Illuminate\Support\Facades\Log::info('ProcessMetaWebhook: Rule MATCHED!', [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                    ]);
                    $replyDispatcher->dispatch($connection, $rule, $item);
                } else {
                    \Illuminate\Support\Facades\Log::info('ProcessMetaWebhook: NO rule matched for event.', [
                        'surface' => $item->surface->value,
                        'parent_id' => $item->parentId,
                        'text' => $item->text,
                    ]);
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
