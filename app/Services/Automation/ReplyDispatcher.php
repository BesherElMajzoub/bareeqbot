<?php

namespace App\Services\Automation;

use App\Data\Meta\IncomingWebhookEvent;
use App\Jobs\SendReply;
use App\Models\AutomationRule;
use App\Models\ChannelConnection;
use Illuminate\Support\Arr;

/**
 * Dispatches a matched rule's actions, in order, as queued SendReply jobs —
 * each delayed by its `delay_seconds`. The jobs are idempotent, so dispatching
 * is safe to repeat.
 */
class ReplyDispatcher
{
    public function dispatch(ChannelConnection $connection, AutomationRule $rule, IncomingWebhookEvent $event): void
    {
        $context = $this->context($event);

        foreach ($rule->actions as $action) {
            SendReply::dispatch(
                channelConnectionId: $connection->id,
                ruleId: $rule->id,
                platform: $event->platform,
                surface: $event->surface,
                sourceObjectId: $event->objectId,
                actorId: $event->actorId,
                actionType: $action->action_type,
                messageTemplate: $action->message_template,
                context: $context,
                parentRef: $event->parentId,
            )->delay(now()->addSeconds($action->delay_seconds));
        }
    }

    /**
     * Placeholders available to reply templates.
     *
     * @return array<string, string|null>
     */
    protected function context(IncomingWebhookEvent $event): array
    {
        return [
            'comment_text' => $event->text,
            'commenter_id' => $event->actorId,
            'commenter_name' => Arr::get($event->raw, 'from.name')
                ?? Arr::get($event->raw, 'from.username'),
        ];
    }
}
