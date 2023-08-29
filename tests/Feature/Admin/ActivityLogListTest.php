<?php

beforeEach(function () {
    $this->admin = createSuperAdmin();
    $this->user = createUser();
});

test('Super Admin can see Activity log table', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.logs.index'))
        ->assertOk();
});

test('User cannot see Activity log table', function () {
    $this->actingAs($this->user)
        ->get(route('admin.logs.index'))
        ->assertForbidden();
});

test('User with permissions can see Activity log table', function () {
    $this->user->givePermissionTo('view dashboard', 'view logs');
    $this->actingAs($this->user)
        ->get(route('admin.logs.index'))
        ->assertOk();
});
