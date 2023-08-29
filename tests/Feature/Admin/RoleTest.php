<?php

beforeEach(function () {
    $this->admin = createSuperAdmin();
    $this->user = createUser();
});

test('Super Admin can see Role table', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.roles.index'))
        ->assertOk();
});

test('User cannot see role table', function () {
    $this->actingAs($this->user)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

test('User with permissions can see role table', function () {
    $this->user->givePermissionTo('view dashboard', 'view roles');
    $this->actingAs($this->user)
        ->get(route('admin.roles.index'))
        ->assertOk();
});
