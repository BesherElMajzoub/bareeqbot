<?php

namespace App\Enums;

enum RuleActionType: string
{
    case PublicReply = 'public_reply';
    case PrivateReply = 'private_reply';
    case Dm = 'dm';
}
