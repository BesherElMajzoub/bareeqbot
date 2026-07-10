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

        abort_if($secret === '' || ! str_starts_with($header, 'sha256='), 403, 'Invalid webhook signature.');

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        abort_unless(hash_equals($expected, $header), 403, 'Invalid webhook signature.');

        return $next($request);
    }
}
