<?php

namespace App\Notifications;

use App\Models\ChannelConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the tenant owner when a connection's Meta token stops working
 * (expired / revoked by the user on Facebook's side) and automation for that
 * asset has been paused until they reconnect.
 */
class ChannelConnectionRevoked extends Notification
{
    use Queueable;

    public function __construct(
        // Named to avoid colliding with Queueable's own public $connection property.
        public ChannelConnection $channelConnection,
        public string $reason,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'channel_connection_revoked',
            'channel_connection_id' => $this->channelConnection->id,
            'name' => $this->channelConnection->name,
            'platform' => $this->channelConnection->platform->value,
            'reason' => $this->reason,
        ];
    }
}
