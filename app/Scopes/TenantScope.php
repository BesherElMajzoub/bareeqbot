<?php

namespace App\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that constrains tenant-owned models to the active tenant.
 *
 * When no tenant is bound (e.g. platform admin or console context) the scope
 * is inactive and does not filter — such contexts are expected to be guarded
 * separately. Use `Model::withoutTenantScope()` to bypass explicitly.
 *
 * @implements Scope<Model>
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if (! $context->has()) {
            return;
        }

        $column = method_exists($model, 'getTenantColumn')
            ? $model->getTenantColumn()
            : 'tenant_id';

        $builder->where(
            $model->getTable().'.'.$column,
            $context->id(),
        );
    }
}
