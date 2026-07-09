<?php

namespace App\Concerns;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Apply to every tenant-owned model. Adds the tenant global scope and
 * auto-fills `tenant_id` from the active TenantContext on create.
 *
 * @method static Builder<static> withoutTenantScope()
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            /** @var Model&self $model */
            $context = app(TenantContext::class);

            if ($context->has() && empty($model->{$model->getTenantColumn()})) {
                $model->{$model->getTenantColumn()} = $context->id();
            }
        });
    }

    public function getTenantColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, $this->getTenantColumn());
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
