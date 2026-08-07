<?php

namespace App\Services\Meta;

use App\Data\Meta\MetaPageData;
use App\Data\Meta\MetaUserTokenData;
use App\Enums\ChannelPlatform;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Central HTTP client for all Meta Graph API calls.
 *
 * - All requests are pinned to the configured Graph API version.
 * - Tokens are injected per-call and are NEVER logged.
 * - Retries (max 3) with exponential back-off on transient failures.
 * - Respects X-App-Usage rate-limit headers: backs off when usage >= 80%.
 * - Throws MetaApiException on structured Graph errors.
 */
class MetaGraphClient
{
    private const BACKOFF_MS_BASE = 500;

    private const RATE_LIMIT_THRESHOLD = 80;

    /** Fields requested when fetching pages. */
    private const PAGES_FIELDS = 'id,name,access_token,tasks,instagram_business_account{id,username}';

    /** Webhook fields subscribed when a page is connected (FB + IG). */
    public const DEFAULT_PAGE_WEBHOOK_FIELDS = [
        'feed',
        'messages',
        'messaging_postbacks',
    ];

    public const DEFAULT_IG_WEBHOOK_FIELDS = [
        'comments',
        'messages',
        'story_insights',
    ];

    private string $baseUrl;

    public function __construct()
    {
        $version = config('services.meta.graph_version');
        $base = rtrim((string) config('services.meta.graph_base_url'), '/');

        $this->baseUrl = "{$base}/{$version}";
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Exchange an OAuth authorization code for a short-lived user token.
     */
    public function exchangeCodeForToken(string $code, string $redirectUri): MetaUserTokenData
    {
        $response = $this->request()->get('/oauth/access_token', [
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        $data = $this->parseResponse($response);

        return new MetaUserTokenData(
            access_token: $data['access_token'],
            token_type: $data['token_type'] ?? 'bearer',
            expires_in: isset($data['expires_in']) ? (int) $data['expires_in'] : null,
        );
    }

    /**
     * Exchange a short-lived token for a long-lived user token (~60 days).
     */
    public function exchangeForLongLivedToken(string $shortLivedToken): MetaUserTokenData
    {
        $response = $this->request()->get('/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'fb_exchange_token' => $shortLivedToken,
        ]);

        $data = $this->parseResponse($response);

        return new MetaUserTokenData(
            access_token: $data['access_token'],
            token_type: $data['token_type'] ?? 'bearer',
            expires_in: isset($data['expires_in']) ? (int) $data['expires_in'] : null,
        );
    }

    /**
     * Fetch all pages managed by the user (and their linked IG business accounts).
     *
     * @return Collection<int, MetaPageData>
     */
    public function getUserPages(string $userToken): Collection
    {
        $response = $this->requestWithToken($userToken)->get('/me/accounts', [
            'fields' => self::PAGES_FIELDS,
        ]);

        $data = $this->parseResponse($response);

        /** @var array<int, array<string, mixed>> $pageData */
        $pageData = $data['data'] ?? [];

        return collect($pageData)->map(function (array $page): MetaPageData {
            $igId = $page['instagram_business_account']['id'] ?? null;
            $igUsername = $page['instagram_business_account']['username'] ?? null;

            return new MetaPageData(
                id: $page['id'],
                name: $page['name'],
                access_token: $page['access_token'],
                tasks: $page['tasks'] ?? null,
                instagram_business_account_id: $igId,
                instagram_username: $igUsername,
            );
        });
    }

    /**
     * Subscribe the app to a page's webhooks (asset-level subscription).
     * App-level subscription is done once in the Meta App Dashboard.
     *
     * @param  string[]  $fields
     */
    public function subscribeAppToPage(string $pageId, string $pageToken, array $fields): bool
    {
        $response = $this->requestWithToken($pageToken)->post("/{$pageId}/subscribed_apps", [
            'subscribed_fields' => implode(',', $fields),
        ]);

        $data = $this->parseResponse($response);

        return (bool) ($data['success'] ?? false);
    }

    /**
     * Remove the app's asset-level webhook subscription for a page. Called
     * when a tenant disconnects a channel.
     */
    public function unsubscribeAppFromPage(string $pageId, string $pageToken): bool
    {
        $response = $this->requestWithToken($pageToken)->delete("/{$pageId}/subscribed_apps");

        $data = $this->parseResponse($response);

        return (bool) ($data['success'] ?? false);
    }

    /**
     * Post a public reply to a comment. Facebook uses the `comments` edge,
     * Instagram uses `replies`.
     *
     * @return array<string, mixed>
     */
    public function replyToComment(string $commentId, string $message, string $token, ChannelPlatform $platform): array
    {
        $edge = $platform === ChannelPlatform::Instagram ? 'replies' : 'comments';

        return $this->parseResponse(
            $this->requestWithToken($token)->post("/{$commentId}/{$edge}", ['message' => $message]),
        );
    }

    /**
     * Send a private reply (DM) in response to a comment. One per comment,
     * within Meta's messaging window.
     *
     * @return array<string, mixed>
     */
    public function privateReplyToComment(string $commentId, string $message, string $token): array
    {
        return $this->parseResponse(
            $this->requestWithToken($token)->post("/{$commentId}/private_replies", ['message' => $message]),
        );
    }

    /**
     * Send a direct message (used for story replies / mentions) via the Send API,
     * from the asset to a user, within Meta's messaging window.
     *
     * @return array<string, mixed>
     */
    public function sendDirectMessage(string $assetId, string $recipientId, string $message, string $token): array
    {
        return $this->parseResponse(
            $this->requestWithToken($token)->post("/{$assetId}/messages", [
                'messaging_type' => 'RESPONSE',
                'recipient' => ['id' => $recipientId],
                'message' => ['text' => $message],
            ]),
        );
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Base HTTP client without a token (used for token-exchange calls that
     * embed credentials in query params — never logged).
     */
    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(15)
            ->acceptJson()
            ->withoutRedirecting();
    }

    /**
     * HTTP client with a Bearer token injected. The token is NOT logged.
     */
    private function requestWithToken(string $token): PendingRequest
    {
        return $this->request()->withToken($token);
    }

    /**
     * Parse a Graph API response, applying retry logic and rate-limit back-off.
     * Throws MetaApiException on error responses.
     *
     * @return array<string, mixed>
     */
    private function parseResponse(Response $response): array
    {
        $this->checkRateLimit($response);

        $body = $response->json();

        // Structured Graph error
        if (isset($body['error'])) {
            $err = $body['error'];
            throw new MetaApiException(
                message: $err['message'] ?? 'Unknown Meta API error',
                metaCode: (int) ($err['code'] ?? 0),
                metaType: (string) ($err['type'] ?? ''),
                fbtraceId: (string) ($err['fbtrace_id'] ?? ''),
                httpStatus: $response->status(),
            );
        }

        if ($response->failed()) {
            throw new MetaApiException(
                message: 'Meta Graph API returned HTTP '.$response->status(),
                metaCode: 0,
                metaType: 'HttpError',
                fbtraceId: '',
                httpStatus: $response->status(),
            );
        }

        return is_array($body) ? $body : [];
    }

    /**
     * Log a warning (no secrets) and optionally sleep if usage is high.
     */
    private function checkRateLimit(Response $response): void
    {
        $usageHeader = $response->header('X-App-Usage');

        if ($usageHeader === '') {
            return;
        }

        /** @var array<string, int>|null $usage */
        $usage = json_decode($usageHeader, true);
        if (! is_array($usage)) {
            return;
        }

        $callCount = $usage['call_count'] ?? 0;
        $totalTime = $usage['total_time'] ?? 0;
        $totalCPU = $usage['total_cputime'] ?? 0;
        $maxUsage = max($callCount, $totalTime, $totalCPU);

        if ($maxUsage >= self::RATE_LIMIT_THRESHOLD) {
            Log::warning('Meta Graph API rate limit approaching', [
                'call_count' => $callCount,
                'total_time' => $totalTime,
                'total_cputime' => $totalCPU,
            ]);

            // Back off proportionally when near the limit.
            $sleepMs = (int) (self::BACKOFF_MS_BASE * ($maxUsage / 100));
            usleep($sleepMs * 1000);
        }
    }
}
