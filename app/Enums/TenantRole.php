<?php

namespace App\Enums;

/**
 * Roles scoped to a single tenant (Spatie team = tenant).
 */
enum TenantRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
