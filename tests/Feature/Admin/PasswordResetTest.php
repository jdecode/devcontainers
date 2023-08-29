<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = createSuperAdmin('test@example.com', 'Password@123');
});

test('See forgot password form', function () {
    $this->get(route('admin.password.request'))->assertOk();
});

test('Email password reset', function () {
    Notification::fake();
    $admin = $this->admin;
    $this->post(route('admin.password.email'), ['email' => $admin->email]);
    Notification::assertSentTo(
        $admin,
        ResetPassword::class,
        function (object $notification) use ($admin) {
            $this->get(route(
                'admin.password.reset',
                ['token' => $notification->token]
            ))->assertOk();
            Event::fake();
            $this->post(route('admin.password.store'), [
                'token' => $notification->token,
                'email' => $admin->email,
                'password' => 'Password@1234',
                'password_confirmation' => 'Password@1234',
            ])
                ->assertValid()
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('admin.login'));
            Event::assertDispatched(PasswordReset::class);
            return true;
        }
    );
});

test('Cannot email password reset if no token', function () {
    Notification::fake();
    $admin = $this->admin;
    $this->post(route('admin.password.email'), ['email' => $admin->email]);
    Notification::assertSentTo(
        $admin,
        ResetPassword::class,
        function (object $notification) use ($admin) {
            $this->post(route('admin.password.store'), [
                'email' => $admin->email,
                'password' => 'Password@1234',
                'password_confirmation' => 'Password@1234',
            ])
                ->assertInvalid();
            return true;
        }
    );
});

test('Cannot email password reset if password not pass requirements', function () {
    Notification::fake();
    $admin = $this->admin;
    $this->post(route('admin.password.email'), ['email' => $admin->email]);
    Notification::assertSentTo(
        $admin,
        ResetPassword::class,
        function (object $notification) use ($admin) {
            $this->post(route('admin.password.store'), [
                'email' => $admin->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
                ->assertInvalid();
            return true;
        }
    );
});
