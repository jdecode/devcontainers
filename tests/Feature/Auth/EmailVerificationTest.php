<?php

use App\Models\User;
use App\Notifications\ProfileImageUploadNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Arr;

test('Email can be verified', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    $this->assertTrue($user->fresh()->hasVerifiedEmail());
    $url = config('app.frontend_url');
    $path = config('frontend.verified_email_redirect');
    $param = Arr::query(['user_name' => $user->first_name]);
    $response->assertRedirect($url . $path . "?$param");
});

test('Email cannot be verified more then once', function () {
    $user = User::factory()->create();
    Event::fake();
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );
    $response = $this->actingAs($user)->get($verificationUrl);
    Event::assertNotDispatched(Verified::class);
    $this->assertTrue($user->fresh()->hasVerifiedEmail());
    $url = config('app.frontend_url');
    $path = config('frontend.verified_email_redirect');
    $param = Arr::query(['user_name' => $user->first_name]);
    $response->assertRedirect($url . $path . "?$param");
});

test('Email is not verified with invalid hash', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    $this->actingAs($user)->get($verificationUrl);

    $this->assertFalse($user->fresh()->hasVerifiedEmail());
});
