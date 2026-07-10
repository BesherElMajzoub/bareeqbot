<?php

namespace App\Jobs;

use App\Actions\Connections\MarkConnectionError;
use App\Enums\ChannelPlatform;
use App\Enums\ReplyLogStatus;
use App\Enums\RuleActionType;
use App\Enums\WebhookSurface;
use App\Models\ChannelConnection;
use App\Models\ReplyLog;
use App\Services\Automation\TemplateRenderer;
use App\Services\Meta\MetaApiException;
use App\Services\Meta\MetaGraphClient;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Sends a single rule action's reply, idempotently. The reply_logs unique index
 * on (platform, source_object_id, action_type) guarantees a given object is
 * never replied to twice with the same action, even across retries.
 *
 * Runs in a queue with no auth — the tenant is bound from the connection.
 */
class SendReply implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string|null>  $context
     */
    public function __construct(
        public int $channelConnectionId,
        public int $ruleId,
        public ChannelPlatform $platform,
        public WebhookSurface $surface,
        public string $sourceObjectId,
        public ?string $actorId,
        public RuleActionType $actionType,
        public string $messageTemplate,
        public array $context,
        public ?string $parentRef = null,
    ) {}

    public function handle(
        MetaGraphClient $client,
        TemplateRenderer $renderer,
        TenantContext $tenantContext,
        PermissionRegistrar $permissions,
        MarkConnectionError $markConnectionError,
    ): void {
        $connection = ChannelConnection::withoutTenantScope()->find($this->channelConnectionId);

        if ($connection === null || ! $connection->isActive()) {
            return;
        }

        $tenant = $connection->tenant;
        if ($tenant === null) {
            return;
        }

        $tenantContext->set($tenant);
        $permissions->setPermissionsTeamId($tenant->id);

        $log = null;

        try {
            // Reserve the idempotency slot for this (platform, object, action).
            $log = ReplyLog::firstOrCreate(
                [
                    'platform' => $this->platform,
                    'source_object_id' => $this->sourceObjectId,
                    'action_type' => $this->actionType,
                ],
                [
                    'tenant_id' => $tenant->id,
                    'channel_connection_id' => $connection->id,
                    'rule_id' => $this->ruleId,
                    'surface' => $this->surface,
                    'actor_id' => $this->actorId,
                    'parent_ref' => $this->parentRef,
                    'status' => ReplyLogStatus::Skipped,
                ],
            );

            // Already sent by a previous run → dedupe, never send twice.
            if (! $log->wasRecentlyCreated && $log->status === ReplyLogStatus::Sent) {
                return;
            }

            $message = $renderer->render($this->messageTemplate, $this->context);

            $sent = match ($this->actionType) {
                RuleActionType::PublicReply => (bool) $client->replyToComment($this->sourceObjectId, $message, $connection->access_token, $this->platform),
                RuleActionType::PrivateReply => (bool) $client->privateReplyToComment($this->sourceObjectId, $message, $connection->access_token),
                RuleActionType::Dm => $this->actorId !== null
                    && (bool) $client->sendDirectMessage($connection->provider_account_id, $this->actorId, $message, $connection->access_token),
            };

            $log->update($sent
                ? ['status' => ReplyLogStatus::Sent, 'responded_at' => now(), 'error' => null]
                : ['status' => ReplyLogStatus::Skipped]);
        } catch (Throwable $e) {
            $log?->update(['status' => ReplyLogStatus::Failed, 'error' => mb_substr($e->getMessage(), 0, 500)]);

            // An expired/revoked token will fail on every retry — mark the
            // connection errored and notify the owner instead of retrying futilely.
            if ($e instanceof MetaApiException && $e->isAuthError()) {
                $markConnectionError->handle($connection, $e->getMessage());

                return;
            }

            throw $e;
        } finally {
            $tenantContext->forget();
        }
    }
}
