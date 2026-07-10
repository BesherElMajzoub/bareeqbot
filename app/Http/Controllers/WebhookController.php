<?php

namespace App\Http\Controllers;

use App\Enums\WebhookEventStatus;
use App\Jobs\ProcessMetaWebhook;
use App\Models\WebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

/**
 * Handles the Meta webhook endpoint.
 *
 * GET  — verification challenge (Meta registers the callback URL).
 * POST — signed event ingestion: the `meta.signature` middleware verifies the
 *        HMAC, then we persist the raw payload and hand off to a queued job so
 *        we can return 200 fast (Meta retries on non-200 / slowness).
 */
class WebhookController extends Controller
{
    /**
     * GET /webhooks/meta
     *
     * Meta sends this when you register the webhook callback URL in the App Dashboard.
     * Returns hub.challenge when hub.verify_token matches our config.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
        $verifyToken = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

        $configToken = config('services.meta.webhook_verify_token');

        if ($mode === 'subscribe' && hash_equals((string) $configToken, (string) $verifyToken)) {
            return response((string) $challenge, 200)
                ->header('Content-Type', 'text/plain');
        }

        abort(403, 'Webhook verification failed.');
    }

    /**
     * POST /webhooks/meta
     *
     * Signature already verified by the `meta.signature` middleware. Persist the
     * raw payload for replay/debugging, queue processing, and return 200 fast.
     */
    public function receive(Request $request): Response
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();

        $event = WebhookEvent::create([
            'platform' => (string) Arr::get($payload, 'object', 'unknown'),
            'object_type' => Arr::get($payload, 'object'),
            'object_id' => Arr::get($payload, 'entry.0.id') !== null ? (string) Arr::get($payload, 'entry.0.id') : null,
            'signature_valid' => true,
            'raw_payload' => $payload,
            'received_at' => now(),
            'status' => WebhookEventStatus::Received,
        ]);

        ProcessMetaWebhook::dispatch($event->id);

        return response('', 200);
    }
}
