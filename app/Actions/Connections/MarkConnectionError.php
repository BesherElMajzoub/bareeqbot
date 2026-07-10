<?php

namespace App\Actions\Connections;

use App\Enums\ChannelStatus;
use App\Models\ChannelConnection;
use App\Notifications\ChannelConnectionRevoked;

/**
 * Marks a connection as errored when a Meta call fails with an auth error
 * (expired/revoked token) during automation, and notifies the tenant owner
 * once. Automation for this asset is effectively paused until the owner
 * reconnects (ChannelConnection::isActive() is false for non-active statuses).
 */
class MarkConnectionError
{
    public function handle(ChannelConnection $connection, string $reason): void
    {
        // Already flagged — avoid re-notifying on every retry/subsequent failure.
        if ($connection->status === ChannelStatus::Error) {
            return;
        }

        $connection->update(['status' => ChannelStatus::Error]);

        $owner = $connection->tenant?->owner;
        $owner?->notify(new ChannelConnectionRevoked($connection, $reason));
    }
}
