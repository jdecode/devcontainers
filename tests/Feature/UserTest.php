<?php

beforeEach(function () {
    $this->user = createUser();
});

test('Get user list', function () {
    $this->actingAs($this->user)
        ->get(route('users.index'))
        ->assertOk()
        ->assertResourcePagination();
});

test('Cannot get user list without permission', function () {
    $this->roleUser = getRoleUser();
    $this->roleUser->revokePermissionTo('view users');
    $this->actingAs($this->user)
        ->get(route('users.index'))
        ->assertForbidden();
});
