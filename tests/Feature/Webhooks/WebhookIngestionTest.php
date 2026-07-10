<?php

use App\Jobs\ProcessMetaWebhook;
use App\Models\WebhookEvent;
use Illuminate\Support\Facades\Queue;

test('a validly signed webhook is stored and queued for processing', function () {
    Queue::fake();

    $response = postSignedMetaWebhook(fbCommentPayload('111', 'c1', '999'));

    $response->assertOk();
    expect(WebhookEvent::count())->toBe(1);
    $event = WebhookEvent::first();
    expect($event->platform)->toBe('page')
        ->and($event->object_id)->toBe('111')
        ->and($event->signature_valid)->toBeTrue();

    Queue::assertPushed(ProcessMetaWebhook::class);
});

test('a webhook with an invalid signature is rejected and not stored', function () {
    Queue::fake();
    config(['services.meta.app_secret' => 'real-secret']);

    $body = (string) json_encode(fbCommentPayload('111', 'c1', '999'));
    $response = $this->call('POST', route('webhooks.meta.receive'), [], [], [], [
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256=deadbeef',
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertForbidden();
    expect(WebhookEvent::count())->toBe(0);
    Queue::assertNothingPushed();
});

test('a webhook with no signature header is rejected', function () {
    config(['services.meta.app_secret' => 'real-secret']);

    $this->call('POST', route('webhooks.meta.receive'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], (string) json_encode(fbCommentPayload('111', 'c1', '999')))
        ->assertForbidden();

    expect(WebhookEvent::count())->toBe(0);
});
