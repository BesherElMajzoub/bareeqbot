<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the `X-Hub-Signature-256` HMAC that Meta signs every webhook POST
 * with, using the app secret and a constant-time comparison. Rejects with 403
 * on any mismatch so unsigned/forged payloads never reach processing.
 */
class VerifyMetaWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('services.meta.app_secret');
        $header = (string) $request->header('X-Hub-Signature-256', '');

        if ($secret === '') {
            \Illuminate\Support\Facades\Log::error('Meta Webhook Failed: META_APP_SECRET is empty in configuration/env.');
            abort(403, 'Invalid webhook signature: app secret is empty.');
        }

        if (! str_starts_with($header, 'sha256=')) {
            \Illuminate\Support\Facades\Log::warning('Meta Webhook Failed: Missing or invalid X-Hub-Signature-256 header.', [
                'header' => $header,
                'ip' => $request->ip(),
            ]);
            abort(403, 'Invalid webhook signature: missing header.');
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $header)) {
            \Illuminate\Support\Facades\Log::warning('Meta Webhook Failed: Signature mismatch.', [
                'received_header' => $header,
                'expected' => $expected,
            ]);
            abort(403, 'Invalid webhook signature.');
        }

        return $next($request);
    }
}
