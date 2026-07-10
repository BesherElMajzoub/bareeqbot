<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tenancy\ActivateTenant;
use App\Actions\Tenancy\SuspendTenant;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TenantController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Tenant::class);

        $tenants = QueryBuilder::for(Tenant::class)
            ->allowedFilters(AllowedFilter::exact('status'))
            ->allowedSorts('created_at', 'name')
            ->defaultSort('-created_at')
            ->with('owner:id,name,email')
            ->withCount('channelConnections')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/tenants/index', ['tenants' => $tenants]);
    }

    public function suspend(Tenant $tenant, SuspendTenant $action): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $action->handle($tenant);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('admin.tenant_suspended')]);

        return back();
    }

    public function activate(Tenant $tenant, ActivateTenant $action): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $action->handle($tenant);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('admin.tenant_activated')]);

        return back();
    }
}
