<?php

use App\Enums\PlatformRole;
use App\Enums\TenantRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Bind the base TestCase and RefreshDatabase to the Feature and Unit
| suites so every test runs against a fresh, migrated database. Foundational
| reference data (roles/permissions) is seeded from TestCase::setUp so that
| both Pest and class-based tests get it.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a user who owns a fresh tenant, with the tenant `owner` role assigned
 * within that tenant's team. Returns [User, Tenant].
 *
 * @return array{0: User, 1: Tenant}
 */
function createTenantOwner(): array
{
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create(['owner_user_id' => $user->id]);
    $user->tenants()->attach($tenant->id, ['role' => TenantRole::Owner->value]);
    $user->forceFill(['current_tenant_id' => $tenant->id])->save();

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    $user->assignRole(TenantRole::Owner->value);

    return [$user, $tenant];
}

/**
 * Create a Bariq platform staff user with a platform role (super_admin by
 * default) assigned within the platform team, so `hasPermissionTo()` checks
 * on /admin routes resolve correctly.
 */
function createPlatformStaff(PlatformRole $role = PlatformRole::SuperAdmin): User
{
    $user = User::factory()->create(['is_platform_staff' => true]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(config('bariq.platform_team_id'));
    $user->assignRole($role->value);

    return $user;
}

/**
 * POST a JSON payload to the Meta webhook endpoint, signed with the given app
 * secret exactly as Meta signs it (`X-Hub-Signature-256: sha256=<hmac>`).
 *
 * @param  array<string, mixed>  $payload
 */
function postSignedMetaWebhook(array $payload, string $secret = 'test-app-secret'): TestResponse
{
    config(['services.meta.app_secret' => $secret]);

    $body = (string) json_encode($payload);
    $signature = 'sha256='.hash_hmac('sha256', $body, $secret);

    return test()->call('POST', route('webhooks.meta.receive'), [], [], [], [
        'HTTP_X_HUB_SIGNATURE_256' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

/**
 * A Facebook page `feed` comment webhook payload.
 *
 * @return array<string, mixed>
 */
function fbCommentPayload(string $pageId, string $commentId, string $fromId, string $text = 'hello'): array
{
    return [
        'object' => 'page',
        'entry' => [[
            'id' => $pageId,
            'time' => now()->timestamp,
            'changes' => [[
                'field' => 'feed',
                'value' => [
                    'item' => 'comment',
                    'verb' => 'add',
                    'comment_id' => $commentId,
                    'post_id' => $pageId.'_post',
                    'from' => ['id' => $fromId, 'name' => 'Commenter'],
                    'message' => $text,
                ],
            ]],
        ]],
    ];
}

/**
 * An Instagram `comments` webhook payload.
 *
 * @return array<string, mixed>
 */
function igCommentPayload(string $igUserId, string $commentId, string $fromId, string $text = 'hello'): array
{
    return [
        'object' => 'instagram',
        'entry' => [[
            'id' => $igUserId,
            'time' => now()->timestamp,
            'changes' => [[
                'field' => 'comments',
                'value' => [
                    'id' => $commentId,
                    'text' => $text,
                    'from' => ['id' => $fromId, 'username' => 'commenter'],
                    'media' => ['id' => $igUserId.'_media'],
                ],
            ]],
        ]],
    ];
}

/**
 * A story-reply messaging webhook payload.
 *
 * @return array<string, mixed>
 */
function storyReplyPayload(string $assetId, string $messageId, string $fromId, string $text = 'nice story'): array
{
    return [
        'object' => 'instagram',
        'entry' => [[
            'id' => $assetId,
            'messaging' => [[
                'sender' => ['id' => $fromId],
                'recipient' => ['id' => $assetId],
                'message' => [
                    'mid' => $messageId,
                    'text' => $text,
                    'reply_to' => ['story' => ['id' => 'story-1', 'url' => 'https://example.test/s']],
                ],
            ]],
        ]],
    ];
}

/**
 * A story-mention messaging webhook payload.
 *
 * @return array<string, mixed>
 */
function storyMentionPayload(string $assetId, string $messageId, string $fromId): array
{
    return [
        'object' => 'instagram',
        'entry' => [[
            'id' => $assetId,
            'messaging' => [[
                'sender' => ['id' => $fromId],
                'recipient' => ['id' => $assetId],
                'message' => [
                    'mid' => $messageId,
                    'attachments' => [[
                        'type' => 'story_mention',
                        'payload' => ['url' => 'https://example.test/story-mention'],
                    ]],
                ],
            ]],
        ]],
    ];
}

/**
 * An Instagram `comments` webhook (alias kept for clarity in Phase 6 tests).
 *
 * @return array<string, mixed>
 */
function instagramCommentPayload(string $igUserId, string $commentId, string $fromId, string $text = 'hello'): array
{
    return igCommentPayload($igUserId, $commentId, $fromId, $text);
}
