<?php

namespace App\Services\Meta;

use App\Data\Meta\MetaPageData;
use App\Data\Meta\MetaUserTokenData;
use Illuminate\Support\Collection;

/**
 * Handles the Facebook Login for Business OAuth 2.0 flow.
 *
 * Responsibilities:
 *  1. Build the OAuth authorization URL with the correct scopes and state.
 *  2. Handle the callback: exchange code → short-lived → long-lived → fetch pages.
 *
 * All Graph I/O is delegated to MetaGraphClient.
 * Tokens are never logged.
 */
class FacebookOAuthService
{
    /**
     * Scopes required by Bariq.
     *
     * Instagram-specific scopes (instagram_basic, instagram_manage_comments,
     * instagram_manage_messages) require the "Instagram Graph API" product to be
     * added to the Meta App. They are omitted here and will be added in Phase 6
     * when Instagram comment/message APIs are implemented.
     *
     * Instagram business account data (id, username) is still readable via the
     * instagram_business_account{id,username} edge on /me/accounts using only
     * the Page Access Token — no extra scopes needed for Phase 3.
     */
    private const SCOPES = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_engagement',
        'pages_messaging',
        'pages_manage_metadata',
        'business_management',
    ];

    public function __construct(
        private readonly MetaGraphClient $graph,
    ) {}

    /**
     * Build the Facebook Login dialog URL.
     *
     * The caller must generate a cryptographically random state, store it in
     * the session, and pass it here. The callback verifies it (CSRF guard).
     */
    public function redirectUrl(string $state, string $redirectUri): string
    {
        $params = http_build_query([
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => implode(',', self::SCOPES),
            'response_type' => 'code',
        ]);

        return 'https://www.facebook.com/dialog/oauth?'.$params;
    }

    /**
     * Handle the OAuth callback:
     *  1. Exchange the one-time code for a short-lived token.
     *  2. Exchange the short-lived token for a long-lived token (~60 days).
     *  3. Fetch the user's managed pages (+ linked IG business accounts).
     *
     * @return array{token: MetaUserTokenData, pages: Collection<int, MetaPageData>}
     */
    public function handleCallback(string $code, string $redirectUri): array
    {
        $shortLived = $this->graph->exchangeCodeForToken($code, $redirectUri);
        $longLived = $this->graph->exchangeForLongLivedToken($shortLived->access_token);
        $pages = $this->graph->getUserPages($longLived->access_token);

        return [
            'token' => $longLived,
            'pages' => $pages,
        ];
    }
}
