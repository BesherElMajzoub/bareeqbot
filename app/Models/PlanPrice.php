<?php

namespace App\Models;

use Database\Factories\PlanPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property int $duration_months
 * @property string $price
 * @property string $currency
 * @property bool $is_active
 */
#[Fillable(['plan_id', 'duration_months', 'price', 'currency', 'is_active'])]
class PlanPrice extends Model
{
    /** @use HasFactory<PlanPriceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
