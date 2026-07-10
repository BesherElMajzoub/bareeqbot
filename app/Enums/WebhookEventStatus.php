<?php

namespace App\Enums;

enum WebhookEventStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
