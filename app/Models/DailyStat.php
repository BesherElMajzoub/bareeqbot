<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use Database\Factories\DailyStatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Nightly aggregate of a tenant + connection's automation activity for a day.
 * Populated by the `stats:aggregate` command.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $channel_connection_id
 * @property Carbon $date
 * @property int $events_received
 * @property int $replies_sent
 * @property int $dms_sent
 * @property int $failures
 */
#[Fillable([
    'tenant_id',
    'channel_connection_id',
    'date',
    'events_received',
    'replies_sent',
    'dms_sent',
    'failures',
])]
class DailyStat extends Model
{
    /** @use HasFactory<DailyStatFactory> */
    use BelongsToTenant, HasFactory;

    protected $table = 'daily_stats';

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'events_received' => 'integer',
            'replies_sent' => 'integer',
            'dms_sent' => 'integer',
            'failures' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ChannelConnection, $this>
     */
    public function channelConnection(): BelongsTo
    {
        return $this->belongsTo(ChannelConnection::class);
    }
}
