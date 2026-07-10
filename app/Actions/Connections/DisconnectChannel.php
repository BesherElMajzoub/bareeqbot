<?php

namespace App\Actions\Connections;

use App\Enums\ChannelPlatform;
use App\Enums\ChannelStatus;
use App\Models\ChannelConnection;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Support\Facades\Log;

/**
 * Disconnects (revokes) a channel connection.
 *
 * Marks the connection as revoked, freeing quota immediately, and attempts to
 * remove the asset-level webhook subscription on Meta's side. The Graph call
 * is best-effort and non-fatal: if the token is already invalid (the very
 * reason many disconnects happen), the local revoke still succeeds.
 */
class DisconnectChannel
{
    public function __construct(private readonly MetaGraphClient $graph) {}

    public function handle(ChannelConnection $connection): void
    {
        // The subscribed_apps webhook subscription always lives on the FB page,
        // even for Instagram connections (linked_page_id holds the page id there).
        $pageId = $connection->platform === ChannelPlatform::Instagram
            ? $connection->linked_page_id
            : $connection->provider_account_id;

        if ($pageId !== null) {
            try {
                $this->graph->unsubscribeAppFromPage($pageId, $connection->access_token);
            } catch (MetaApiException $e) {
                Log::warning('Failed to remove webhook subscription on disconnect', [
                    'channel_connection_id' => $connection->id,
                    'meta_code' => $e->metaCode,
                    'meta_type' => $e->metaType,
                    'fbtrace_id' => $e->fbtraceId,
                ]);
            }
        }

        $connection->update([
            'status' => ChannelStatus::Revoked,
            'webhook_subscribed' => false,
        ]);
    }
}
