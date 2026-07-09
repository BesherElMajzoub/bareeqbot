<?php

namespace App\Enums;

/**
 * How a subscription was created. `manual` = offline payment + admin
 * activation (v1). `gateway` = a future automated payment provider.
 */
enum SubscriptionSource: string
{
    case Manual = 'manual';
    case Gateway = 'gateway';
}
