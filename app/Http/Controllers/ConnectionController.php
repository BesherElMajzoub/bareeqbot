<?php

namespace App\Http\Controllers;

use App\Actions\Connections\ConnectChannel;
use App\Actions\Connections\DisconnectChannel;
use App\Data\Meta\MetaPageData;
use App\Enums\ChannelPlatform;
use App\Exceptions\QuotaExceededException;
use App\Http\Requests\Connections\StoreConnectionRequest;
use App\Models\ChannelConnection;
use App\Services\Billing\SubscriptionQuota;
use App\Services\Meta\FacebookOAuthService;
use App\Services\Meta\MetaApiException;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionController extends Controller
{
    private string $callbackUri;

    public function __construct()
    {
        $this->callbackUri = config('app.url').'/connections/facebook/callback';
    }

    /**
     * List all channel connections for the current tenant.
     */
    public function index(TenantContext $tenantContext, SubscriptionQuota $quota): Response
    {
        $this->authorize('viewAny', ChannelConnection::class);

        $tenant = $tenantContext->current();
        abort_if($tenant === null, 403);

        $connections = $tenant->channelConnections()
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('connections/index', [
            'connections' => $connections,
            'quota' => [
                'used' => $quota->usedPages($tenant),
                'max' => $quota->maxPages($tenant),
            ],
        ]);
    }

    /**
     * Generate the Facebook Login for Business authorization URL and redirect.
     * Stores a CSRF state token in the session.
     */
    public function redirect(FacebookOAuthService $oauth): RedirectResponse
    {
        $this->authorize('create', ChannelConnection::class);

        $state = Str::random(40);
        session(['meta_oauth_state' => $state]);

        return redirect()->away($oauth->redirectUrl($state, $this->callbackUri));
    }

    /**
     * Handle the OAuth callback from Meta.
     *
     * 1. Verify the state (CSRF guard).
     * 2. Exchange code → long-lived token → fetch pages.
     * 3. Store the result in session and render the asset picker page.
     */
    public function callback(Request $request, FacebookOAuthService $oauth): Response|RedirectResponse
    {
        $this->authorize('create', ChannelConnection::class);

        // CSRF guard.
        if ($request->input('state') !== session('meta_oauth_state')) {
            return redirect()->route('connections.index')
                ->withErrors(['oauth' => __('connections.invalid_state')]);
        }

        if ($request->has('error')) {
            return redirect()->route('connections.index')
                ->withErrors(['oauth' => $request->input('error_description', __('connections.oauth_error'))]);
        }

        $code = $request->string('code')->toString();

        try {
            $result = $oauth->handleCallback($code, $this->callbackUri);
        } catch (MetaApiException $e) {
            return redirect()->route('connections.index')
                ->withErrors(['oauth' => __('connections.meta_error')]);
        }

        // Store result in session so the store action can verify what was offered.
        session([
            'meta_oauth_result' => [
                'pages' => $result['pages']->toArray(),
                'expires_at' => now()->addMinutes(10)->toIso8601String(),
            ],
        ]);

        return Inertia::render('connections/select', [
            'pages' => $result['pages']->values()->toArray(),
        ]);
    }

    /**
     * Store the selected connections after the user picks from the asset picker.
     */
    public function store(
        StoreConnectionRequest $request,
        ConnectChannel $action,
        TenantContext $tenantContext,
    ): RedirectResponse {
        $tenant = $tenantContext->current();
        abort_if($tenant === null, 403);

        $user = $request->user();
        $errors = [];
        $created = 0;

        foreach ($request->validated('selected_assets') as $asset) {
            $platform = $asset['platform'] === 'instagram'
                ? ChannelPlatform::Instagram
                : ChannelPlatform::Facebook;

            $pageData = new MetaPageData(
                id: $asset['id'],
                name: $asset['name'],
                access_token: $asset['access_token'],
                tasks: null,
                instagram_business_account_id: $asset['instagram_business_account_id'] ?? null,
                instagram_username: $asset['instagram_username'] ?? null,
            );

            try {
                $action->handle($tenant, $pageData, $platform, $user);
                $created++;
            } catch (QuotaExceededException) {
                $errors[] = __('connections.quota_exceeded');
                break;
            }
        }

        // Clear the OAuth session result.
        session()->forget(['meta_oauth_state', 'meta_oauth_result']);

        if ($created > 0) {
            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('connections.connected_successfully', ['count' => $created]),
            ]);
        }

        if (! empty($errors)) {
            return redirect()->route('connections.index')->withErrors(['connect' => $errors]);
        }

        return redirect()->route('connections.index');
    }

    /**
     * Revoke (disconnect) a channel connection.
     */
    public function destroy(
        ChannelConnection $channelConnection,
        DisconnectChannel $action,
    ): RedirectResponse {
        $this->authorize('delete', $channelConnection);

        $action->handle($channelConnection);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('connections.disconnected_successfully'),
        ]);

        return redirect()->route('connections.index');
    }
}
