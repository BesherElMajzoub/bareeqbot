<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\SubscriptionSource;
use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $plan_id
 * @property int $duration_months
 * @property string $price
 * @property string $currency
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property SubscriptionStatus $status
 * @property SubscriptionSource $source
 * @property int|null $created_by
 */
#[Fillable(['tenant_id', 'plan_id', 'duration_months', 'price', 'currency', 'starts_at', 'ends_at', 'status', 'source', 'created_by'])]
class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => SubscriptionStatus::class,
            'source' => SubscriptionSource::class,
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Active = status active and not past its end date.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active)
            ->where('ends_at', '>', now());
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active && $this->ends_at->isFuture();
    }
}
