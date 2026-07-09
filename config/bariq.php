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

];
