<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = createSuperAdmin();
    $this->user = createUser();
    $this->roleUser = getRoleUser();
});

test('Super Admin can see Users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('User with permissions can see users', function () {
    $this->user->givePermissionTo('view dashboard', 'view users');
    $this->actingAs($this->user)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('User without permission cannot see users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

test('User with role with permissions can see users', function () {
    $this->roleUser->givePermissionTo('view dashboard', 'view users');
    $this->actingAs($this->user)
        ->get(route('admin.users.index'))
        ->assertOk();
});

test('Super Admin can delete users', function () {
    $testUser = User::factory()->create();
    $id = $testUser->id;
    $this->assertDatabaseHas('users', ['id' => $id]);
    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $testUser));
    $this->assertDatabaseMissing('users', ['id' => $id]);
});

test('Super Admin cannot delete himself', function () {
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    $this->actingAs($this->admin)
        ->delete(route('admin.users.destroy', $this->admin));
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

test('User cannot delete users', function () {
    $testUser = User::factory()->create();
    $id = $testUser->id;
    $this->assertDatabaseHas('users', ['id' => $id]);
    $this->actingAs($this->user)
        ->delete(route('admin.users.destroy', $testUser))
        ->assertForbidden();
    $this->assertDatabaseHas('users', ['id' => $id]);
});

test('User with permissions can delete users', function () {
    $this->user->givePermissionTo('view dashboard', 'delete user');
    $testUser = User::factory()->create();
    $id = $testUser->id;
    $this->assertDatabaseHas('users', ['id' => $id]);
    $this->actingAs($this->user)
        ->delete(route('admin.users.destroy', $testUser));
    $this->assertDatabaseMissing('users', ['id' => $id]);
});

test('Super Admin can edit users', function () {
    $testUser = User::factory()->create();
    $this->actingAs($this->admin)
        ->get(route('admin.users.edit', $testUser))
        ->assertOk();
});

test('User cannot edit users', function () {
    $testUser = User::factory()->create();
    $this->actingAs($this->user)
        ->get(route('admin.users.edit', $testUser))
        ->assertForbidden();
});

test('User with permissions can edit users', function () {
    $this->user->givePermissionTo('view dashboard', 'update user');
    $testUser = User::factory()->create();
    $this->actingAs($this->user)
        ->get(route('admin.users.edit', $testUser))
        ->assertOk();
});

test('Super Admin can update users', function () {
    $testUser = User::factory()->create();
    $id = $testUser->id;
    $this->actingAs($this->admin)
        ->patch(route('admin.users.update', $testUser), [
            'first_name' => 'test123',
            'role' => [config('constants.roles.super_admin')],
        ])
        ->assertValid();
    $this->assertDatabaseHas('users', [
        'id' => $id,
        'first_name' => 'test123',
    ]);
    expect($testUser)->hasRole(config('constants.roles.super_admin'));
});

test('User cannot update users', function () {
    $testUser = User::factory()->create();
    $id = $testUser->id;
    $first_name = $testUser->first_name;
    $this->actingAs($this->user)
        ->patch(
            route('admin.users.update', $testUser),
            ['first_name' => 'test123']
        )
        ->assertForbidden();
    $this->assertDatabaseHas('users', [
        'id' => $id,
        'first_name' => $first_name,
    ]);
});

test('User with permissions can update users', function () {
    $this->user->givePermissionTo('view dashboard', 'update user');
    $testUser = User::factory()->create();
    $id = $testUser->id;
    $this->actingAs($this->user)
        ->patch(
            route('admin.users.update', $testUser),
            ['first_name' => 'test123']
        )
        ->assertValid();
    $this->assertDatabaseHas('users', [
        'id' => $id,
        'first_name' => 'test123',
    ]);
});

test('Super Admin can add users', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.users.create'))
        ->assertOk();
});

test('User cannot add users', function () {
    $this->actingAs($this->user)
        ->get(route('admin.users.create'))
        ->assertForbidden();
});

test('User with permissions can add users', function () {
    $this->user->givePermissionTo('view dashboard', 'create user');
    $this->actingAs($this->user)
        ->get(route('admin.users.create'))
        ->assertOk();
});

test('Super Admin can store users', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.users.store'), [
            'first_name' => 'example',
            'last_name' => 'test',
            'email' => 'example_test@founderandlightning.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => ['user'],
        ])
        ->assertValid();
    $this->assertDatabaseHas('users', [
        'first_name' => 'example',
        'last_name' => 'test',
        'email' => 'example_test@founderandlightning.com',
    ]);
});

test('User cannot store user', function () {
    $this->actingAs($this->user)
        ->post(route('admin.users.store'), [
            'first_name' => 'example',
            'last_name' => 'test',
            'email' => 'example_test@founderandlightning.com',
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
            'role' => ['user'],
        ])
        ->assertForbidden();
    $this->assertDatabaseMissing('users', [
        'first_name' => 'example',
        'last_name' => 'test',
        'email' => 'example_test@founderandlightning.com',
    ]);
});

test('Super Admin can mark user email as verified', function () {
    $newUser = createRawUser();
    $newUser->email_verified_at = null;
    $newUser->save();
    $this->actingAs($this->admin)
        ->patch(route('admin.users.verify_email', $newUser));
    $newUser->refresh();
    expect($newUser)->email_verified_at->not->toBeNull();
});
