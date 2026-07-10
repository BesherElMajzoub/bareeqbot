<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\ChannelPlatform;
use App\Enums\ChannelStatus;
use Database\Factories\ChannelConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property ChannelPlatform $platform
 * @property string $provider_account_id
 * @property string|null $linked_page_id
 * @property string $name
 * @property string|null $username
 * @property string $access_token
 * @property Carbon|null $token_expires_at
 * @property bool $webhook_subscribed
 * @property ChannelStatus $status
 * @property array<string, mixed>|null $meta
 * @property int|null $connected_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'tenant_id',
    'platform',
    'provider_account_id',
    'linked_page_id',
    'name',
    'username',
    'access_token',
    'token_expires_at',
    'webhook_subscribed',
    'status',
    'meta',
    'connected_by',
])]
#[Hidden(['access_token'])]
class ChannelConnection extends Model
{
    /** @use HasFactory<ChannelConnectionFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'platform' => ChannelPlatform::class,
            'status' => ChannelStatus::class,
            'access_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'webhook_subscribed' => 'boolean',
            'meta' => 'array',
        ];
    }

    /**
     * The user who connected this channel.
     *
     * @return BelongsTo<User, $this>
     */
    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function isActive(): bool
    {
        return $this->status === ChannelStatus::Active;
    }
}
