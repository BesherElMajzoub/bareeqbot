<?php

namespace App\Enums;

/**
 * Global Bariq staff roles (not tenant-scoped).
 */
enum PlatformRole: string
{
    case SuperAdmin = 'super_admin';
    case Support = 'support';
}
