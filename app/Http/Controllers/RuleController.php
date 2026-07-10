<?php

namespace App\Http\Controllers;

use App\Actions\Automation\UpsertAutomationRule;
use App\Http\Requests\Rules\StoreRuleRequest;
use App\Http\Requests\Rules\UpdateRuleRequest;
use App\Models\AutomationRule;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RuleController extends Controller
{
    public function index(TenantContext $tenantContext): Response
    {
        $this->authorize('viewAny', AutomationRule::class);

        $tenant = $tenantContext->current();
        abort_if($tenant === null, 403);

        return Inertia::render('rules/index', [
            'rules' => AutomationRule::with(['actions', 'channelConnection:id,name,platform'])
                ->orderByDesc('priority')
                ->orderBy('id')
                ->get(),
            'connections' => $tenant->channelConnections()->get(['id', 'name', 'platform']),
        ]);
    }

    public function store(StoreRuleRequest $request, UpsertAutomationRule $action, TenantContext $tenantContext): RedirectResponse
    {
        $tenant = $tenantContext->current();
        abort_if($tenant === null, 403);

        $action->handle($tenant, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('rules.saved')]);

        return to_route('rules.index');
    }

    public function update(UpdateRuleRequest $request, AutomationRule $automationRule, UpsertAutomationRule $action, TenantContext $tenantContext): RedirectResponse
    {
        $tenant = $tenantContext->current();
        abort_if($tenant === null, 403);

        $action->handle($tenant, $request->validated(), $automationRule);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('rules.saved')]);

        return to_route('rules.index');
    }

    public function destroy(AutomationRule $automationRule): RedirectResponse
    {
        $this->authorize('delete', $automationRule);

        $automationRule->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('rules.deleted')]);

        return to_route('rules.index');
    }
}
