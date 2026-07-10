<?php

beforeEach(fn () => config(['services.meta.webhook_verify_token' => 'verify-me']));

test('the challenge is echoed back when the verify token matches', function () {
    $this->get(route('webhooks.meta.verify', [
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'verify-me',
        'hub_challenge' => '1234567',
    ]))->assertOk()->assertSee('1234567');
});

test('verification is rejected when the token does not match', function () {
    $this->get(route('webhooks.meta.verify', [
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'wrong',
        'hub_challenge' => '1234567',
    ]))->assertForbidden();
});
