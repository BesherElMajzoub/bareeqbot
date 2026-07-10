<?php

namespace App\Actions\Connections;

use App\Data\Meta\MetaPageData;
use App\Enums\ChannelPlatform;
use App\Enums\ChannelStatus;
use App\Exceptions\QuotaExceededException;
use App\Models\ChannelConnection;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Billing\SubscriptionQuota;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Connects a Facebook Page or Instagram business account for a tenant.
 *
 * Sequence (all inside a DB transaction):
 *  1. Enforce quota — abort if the tenant cannot connect more channels.
 *  2. Upsert the channel_connections row by (platform, provider_account_id).
 *     Re-connecting updates the token and resets the status to active.
 *  3. Attempt to subscribe the asset to webhooks via MetaGraphClient.
 *     On failure: set status = error and log a warning (non-fatal in Phase 3).
 *     The connection is saved regardless — the owner can reconnect to retry.
 *
 * Token storage: the Page Access Token is stored via the `encrypted` cast — the plain-text token
 * never appears in logs.
 */
class ConnectChannel
{
    public function __construct(
        private readonly SubscriptionQuota $quota,
        private readonly MetaGraphClient $graph,
    ) {}

    /**
     * @throws QuotaExceededException when the tenant's plan does not allow more connections.
     */
    public function handle(
        Tenant $tenant,
        MetaPageData $page,
        ChannelPlatform $platform,
        User $connectedBy,
    ): ChannelConnection {
        if (! $this->quota->canConnectMore($tenant)) {
            throw new QuotaExceededException;
        }

        return DB::transaction(function () use ($tenant, $page, $platform, $connectedBy) {
            // For Instagram connections, we store the IG user id as provider_account_id
            // and the Facebook page id as linked_page_id.
            $providerId = $platform === ChannelPlatform::Instagram
                ? (string) $page->instagram_business_account_id
                : $page->id;

            /** @var ChannelConnection $connection */
            $connection = ChannelConnection::withoutTenantScope()->updateOrCreate(
                [
                    'platform' => $platform,
                    'provider_account_id' => $providerId,
                ],
                [
                    'tenant_id' => $tenant->id,
                    'linked_page_id' => $platform === ChannelPlatform::Instagram ? $page->id : null,
                    'name' => $page->name,
                    'username' => $platform === ChannelPlatform::Instagram ? $page->instagram_username : null,
                    'access_token' => $page->access_token, // encrypted via cast
                    'token_expires_at' => null,                // long-lived tokens don't carry a fixed expiry
                    'status' => ChannelStatus::Active,
                    'connected_by' => $connectedBy->id,
                ],
            );

            // Subscribe the asset to webhooks (asset-level subscription).
            // Requires the app-level subscription to be configured in Meta Dashboard first.
            // If it fails here (e.g. app-level not set up yet), the connection is still saved
            // with status=error so the owner can see it needs attention and reconnect later.
            $webhookFields = $platform === ChannelPlatform::Instagram
                ? MetaGraphClient::DEFAULT_IG_WEBHOOK_FIELDS
                : MetaGraphClient::DEFAULT_PAGE_WEBHOOK_FIELDS;

            try {
                $this->graph->subscribeAppToPage($page->id, $page->access_token, $webhookFields);
                $connection->update(['webhook_subscribed' => true]);
            } catch (MetaApiException $e) {
                // Non-fatal: log for debugging but keep the connection alive.
                // The owner will see status=error in the UI and can reconnect to retry.
                Log::warning('Webhook subscription failed for page', [
                    'page_name' => $page->name,
                    'page_id' => $page->id,
                    'meta_code' => $e->metaCode,
                    'meta_type' => $e->metaType,
                    'fbtrace_id' => $e->fbtraceId,
                ]);

                $connection->update([
                    'status' => ChannelStatus::Error,
                    'webhook_subscribed' => false,
                ]);
            }

            return $connection->refresh();
        });
    }
}
