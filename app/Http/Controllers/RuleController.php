<?php

namespace App\Http\Controllers;

use App\Actions\Automation\UpsertAutomationRule;
use App\Http\Requests\Rules\StoreRuleRequest;
use App\Http\Requests\Rules\UpdateRuleRequest;
use App\Models\AutomationRule;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /**
     * List the connection's recent posts/media (id + title), for the rules
     * UI's "pick a specific post" dropdown — an alternative to typing a
     * raw post/media id by hand.
     */
    public function posts(Request $request, TenantContext $tenantContext, MetaGraphClient $client): JsonResponse
    {
        $this->authorize('viewAny', AutomationRule::class);

        $tenant = $tenantContext->current();
        abort_if($tenant === null, 403);

        $validated = $request->validate([
            'channel_connection_id' => ['required', 'integer'],
        ]);

        $connection = $tenant->channelConnections()->findOrFail($validated['channel_connection_id']);

        try {
            $posts = $client->listRecentPosts($connection->provider_account_id, $connection->access_token, $connection->platform);
        } catch (MetaApiException $e) {
            Log::warning('RuleController: Failed to fetch posts for target dropdown', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['posts' => []]);
        }

        return response()->json(['posts' => $posts]);
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
