<?php

use App\Actions\Connections\DisconnectChannel;
use App\Enums\ChannelStatus;
use App\Models\ChannelConnection;
use Illuminate\Support\Facades\Http;

test('disconnecting a facebook connection unsubscribes the page and revokes locally', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['success' => true])]);
    $connection = ChannelConnection::factory()->facebook()->create(['provider_account_id' => '111']);

    app(DisconnectChannel::class)->handle($connection);

    expect($connection->fresh()->status)->toBe(ChannelStatus::Revoked)
        ->and($connection->fresh()->webhook_subscribed)->toBeFalse();
    Http::assertSent(fn ($request) => $request->method() === 'DELETE' && str_contains($request->url(), '111/subscribed_apps'));
});

test('disconnecting an instagram connection unsubscribes the linked page, not the ig id', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['success' => true])]);
    $connection = ChannelConnection::factory()->instagram()->create([
        'provider_account_id' => '222', // IG user id
        'linked_page_id' => '333',      // FB page id — subscribed_apps lives here
    ]);

    app(DisconnectChannel::class)->handle($connection);

    Http::assertSent(fn ($request) => str_contains($request->url(), '333/subscribed_apps'));
});

test('a failed unsubscribe call still revokes the connection locally', function () {
    Http::fake(['graph.facebook.com/*' => Http::response([
        'error' => ['message' => 'Error validating access token', 'type' => 'OAuthException', 'code' => 190],
    ], 400)]);
    $connection = ChannelConnection::factory()->facebook()->create();

    app(DisconnectChannel::class)->handle($connection);

    expect($connection->fresh()->status)->toBe(ChannelStatus::Revoked);
});
