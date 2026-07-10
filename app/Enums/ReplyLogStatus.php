<?php

namespace App\Enums;

enum ReplyLogStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case Deduped = 'deduped';
}
