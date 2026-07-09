<?php

use App\Enums\PlatformRole;
use App\Enums\TenantRole;
use Spatie\Permission\Models\Role;

// Roles are seeded globally in TestCase::setUp.

test('it seeds the four tenant and platform roles', function () {
    expect(Role::whereIn('name', ['owner', 'member', 'super_admin', 'support'])->count())->toBe(4);
});

test('the owner role can manage billing but the member role cannot', function () {
    $owner = Role::where('name', TenantRole::Owner->value)->firstOrFail();
    $member = Role::where('name', TenantRole::Member->value)->firstOrFail();

    expect($owner->permissions->pluck('name'))
        ->toContain('manage-billing')
        ->toContain('manage-rules')
        ->and($member->permissions->pluck('name'))
        ->not->toContain('manage-billing')
        ->toContain('manage-rules');
});

test('super_admin has every permission and support is limited', function () {
    $superAdmin = Role::where('name', PlatformRole::SuperAdmin->value)->firstOrFail();
    $support = Role::where('name', PlatformRole::Support->value)->firstOrFail();

    expect($superAdmin->permissions->pluck('name'))->toContain('manage-tenants')
        ->and($support->permissions->pluck('name'))
        ->not->toContain('manage-tenants')
        ->toContain('approve-subscriptions');
});
