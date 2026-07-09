<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Password is hashed by the model's 'hashed' cast — pass it in plain.
        $admin = User::firstOrCreate(
            ['email' => config('bariq.admin.email')],
            [
                'name' => config('bariq.admin.name'),
                'password' => config('bariq.admin.password'),
            ],
        );

        $admin->forceFill([
            'is_platform_staff' => true,
            'email_verified_at' => now(),
        ])->save();

        // Platform roles are assigned outside any tenant team context.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $admin->assignRole(PlatformRole::SuperAdmin->value);
    }
}
