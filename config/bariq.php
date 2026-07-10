<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform super admin (seeded)
    |--------------------------------------------------------------------------
    |
    | Credentials for the initial Bariq staff account created by the
    | PlatformAdminSeeder. Override via the BARIQ_ADMIN_* env vars, and always
    | change the password before seeding a shared environment.
    |
    */

    'admin' => [
        'name' => env('BARIQ_ADMIN_NAME', 'Bariq Admin'),
        'email' => env('BARIQ_ADMIN_EMAIL', 'admin@bariq.test'),
        'password' => env('BARIQ_ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform team id
    |--------------------------------------------------------------------------
    |
    | Spatie's teams feature requires a non-null team id on every role
    | assignment. Real tenant ids start at 1, so we assign global platform
    | roles (super_admin / support) under this sentinel "no tenant" team id.
    |
    */

    'platform_team_id' => 0,

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    |
    | v1 uses the manual provider (offline payment + admin activation). Swap
    | `provider` for an automated gateway later without touching the
    | subscription lifecycle — gateways implement the same BillingProvider
    | contract. `proof_disk` stores uploaded payment receipts.
    |
    */

    'billing' => [
        'provider' => env('BARIQ_BILLING_PROVIDER', 'manual'),
        'currency' => env('BARIQ_BILLING_CURRENCY', 'SYP'),
        'proof_disk' => env('BARIQ_BILLING_PROOF_DISK', 'local'),
    ],

];
