<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => config(['services.meta.webhook_verify_token' => 'verify-me']));

test('the webhook endpoint is rate limited per ip', function () {
    // Override with a tight limit so the test stays fast and deterministic.
    RateLimiter::for('meta-webhook', fn ($request) => Limit::perMinute(2)->by($request->ip()));

    $params = ['hub_mode' => 'subscribe', 'hub_verify_token' => 'verify-me', 'hub_challenge' => '1'];

    $this->get(route('webhooks.meta.verify', $params))->assertOk();
    $this->get(route('webhooks.meta.verify', $params))->assertOk();
    $this->get(route('webhooks.meta.verify', $params))->assertStatus(429);
});
