<?php

beforeEach(function () {
    $this->admin = createSuperAdmin();
    $this->user = createUser();
    $this->roleUser = getRoleUser();
});

test('Super Admin can see Dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('User without permissions cannot see Dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('User with permissions can see Dashboard', function () {
    $this->user->givePermissionTo('view dashboard');
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('User with role with permission can see Dashboard', function () {
    $this->roleUser->givePermissionTo('view dashboard');
    $this->actingAs($this->user)
        ->get(route('admin.dashboard'))
        ->assertOk();
});
