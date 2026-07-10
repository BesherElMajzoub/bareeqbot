<?php

namespace App\Enums;

/**
 * The interaction surface a webhook event targets. Mirrors
 * `automation_rules.trigger_surface` (BARIQ §5).
 */
enum WebhookSurface: string
{
    case PostComment = 'post_comment';
    case StoryReply = 'story_reply';
    case StoryMention = 'story_mention';
}
