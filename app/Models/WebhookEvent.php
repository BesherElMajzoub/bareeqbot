<?php

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Database\Factories\WebhookEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Raw store of every inbound Meta webhook, for idempotent processing, replay
 * and debugging. Not tenant-owned: the tenant is resolved during processing
 * from the asset id, so this table has no `tenant_id`.
 *
 * @property int $id
 * @property string $platform
 * @property string|null $object_type
 * @property string|null $object_id
 * @property bool $signature_valid
 * @property array<string, mixed> $raw_payload
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 * @property WebhookEventStatus $status
 */
#[Fillable([
    'platform',
    'object_type',
    'object_id',
    'signature_valid',
    'raw_payload',
    'received_at',
    'processed_at',
    'status',
])]
class WebhookEvent extends Model
{
    /** @use HasFactory<WebhookEventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'signature_valid' => 'boolean',
            'raw_payload' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'status' => WebhookEventStatus::class,
        ];
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function markProcessed(WebhookEventStatus $status = WebhookEventStatus::Processed): void
    {
        $this->update(['status' => $status, 'processed_at' => now()]);
    }
}
