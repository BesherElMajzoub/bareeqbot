<?php

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a tenant owner can visit the dashboard', function () {
    [$user] = createTenantOwner();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
