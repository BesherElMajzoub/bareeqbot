<?php

namespace App\Enums;

enum ChannelStatus: string
{
    case Active = 'active';
    case Revoked = 'revoked';
    case Error = 'error';
}
