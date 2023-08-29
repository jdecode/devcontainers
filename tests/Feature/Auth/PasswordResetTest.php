<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;

test('Reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post(route('password.email'), ['email' => $user->email]);
    expect($response)->status()->toBe(200);


    Notification::assertSentTo($user, ResetPassword::class);
});

test('Reset password link cannot be requested with non existing email', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => fake()->email]);
    expect($response)->status()->toBe(200);

    Notification::assertNothingSent();
});

test('Reset password link cannot be requested with non valid email', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), ['email' => fake()->name]);
    expect($response)->status()->toBe(302);

    Notification::assertNothingSent();
});

test('Password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (object $notification) use ($user) {
        Event::fake();
        $response = $this->post(route('password.store'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'Password@123',
            'password_confirmation' => 'Password@123',
        ]);
        Event::assertDispatched(PasswordReset::class);
        $response->assertSessionHasNoErrors();

        return true;
    });
});
