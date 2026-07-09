<?php

namespace App\Models;

use App\Concerns\BelongsToTenant;
use App\Enums\SubscriptionRequestStatus;
use Database\Factories\SubscriptionRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $plan_price_id
 * @property string|null $payment_proof_path
 * @property string|null $payer_note
 * @property SubscriptionRequestStatus $status
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $reject_reason
 */
#[Fillable(['tenant_id', 'plan_price_id', 'payment_proof_path', 'payer_note', 'status', 'reviewed_by', 'reviewed_at', 'reject_reason'])]
class SubscriptionRequest extends Model
{
    /** @use HasFactory<SubscriptionRequestFactory> */
    use BelongsToTenant, HasFactory;

    protected function casts(): array
    {
        return [
            'status' => SubscriptionRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PlanPrice, $this>
     */
    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === SubscriptionRequestStatus::Pending;
    }
}
