<?php

use App\Providers\RouteServiceProvider;

test('See login screen', function () {
    $this->get(route('admin.login'))
        ->assertOk();
});

test('Super Admin can login to Admin Dashboard', function () {
    $admin = createSuperAdmin('test@example.com', 'Password@123');
    $this->post(route('admin.login.store'), [
        'email' => 'test@example.com',
        'password' => 'Password@123'
    ])
        ->assertValid();
    expect($admin)->toBeAuthenticated();
});

test('Super Admin should be redirected if authenticated', function () {
    $admin = createSuperAdmin('test@example.com', 'Password@123');
    $this->actingAs($admin)->post(route('admin.login.store'), [
        'email' => 'test@example.com',
        'password' => 'Password@123'
    ])->assertRedirect(RouteServiceProvider::HOME);
    expect($admin)->toBeAuthenticated();
});

test('User cannot login to Admin Dashboard', function () {
    $user = createUser('test@example.com', 'Password@123');
    $this->post(route('admin.login.store'), [
        'email' => 'test@example.com',
        'password' => 'Password@123'
    ])
        ->assertInvalid('email');
    expect($user)->not->toBeAuthenticated();
});

test('User with permissions can login to Admin Dashboard', function () {
    $user = createUser('test@example.com', 'Password@123');
    $user->givePermissionTo('view dashboard');
    $this->post(route('admin.login.store'), [
        'email' => 'test@example.com',
        'password' => 'Password@123'
    ])
        ->assertValid();
    expect($user)->toBeAuthenticated();
});

test('Logout from Dashboard', function () {
    $admin = createSuperAdmin();
    $this->actingAs($admin)->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));
    expect($admin)->not->toBeAuthenticated();
});
