<?php

use App\Enums\TenantRole;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

test('registration creates a tenant and assigns the owner role', function () {
    $this->post(route('register.store'), [
        'name' => 'متجر بريق',
        'email' => 'owner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'owner@example.com')->firstOrFail();
    $tenant = $user->ownedTenants()->first();

    expect($user->ownedTenants()->count())->toBe(1)
        ->and($tenant->name)->toBe('متجر بريق')
        ->and($user->current_tenant_id)->toBe($tenant->id)
        ->and($user->tenants()->first()->pivot->role)->toBe(TenantRole::Owner->value);

    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    expect($user->fresh()->hasRole(TenantRole::Owner->value))->toBeTrue();
});

test('each registration provisions a distinct tenant with a unique slug', function () {
    foreach (['a@example.com', 'b@example.com'] as $email) {
        $this->post(route('register.store'), [
            'name' => 'متجر',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        auth()->logout();
    }

    $slugs = User::whereIn('email', ['a@example.com', 'b@example.com'])
        ->get()
        ->map(fn (User $u) => $u->ownedTenants()->first()->slug);

    expect($slugs)->toHaveCount(2)
        ->and($slugs->unique())->toHaveCount(2);
});
