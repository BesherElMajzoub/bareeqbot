<?php

namespace App\Models;

use App\Enums\PlanPlatformScope;
use Database\Factories\PlanPriceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property int $duration_months
 * @property PlanPlatformScope $platform_scope
 * @property string $price
 * @property string $currency
 * @property bool $is_active
 */
#[Fillable(['plan_id', 'duration_months', 'platform_scope', 'price', 'currency', 'is_active'])]
class PlanPrice extends Model
{
    /** @use HasFactory<PlanPriceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'platform_scope' => PlanPlatformScope::class,
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
