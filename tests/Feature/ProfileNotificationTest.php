<?php

use Illuminate\Auth\Events\Verified;

beforeEach(function () {
    $this->user = createUser();
});

test('Get Profile notifications', function () {
    $this->actingAs($this->user)
        ->get(route('profile-notification.show'))
        ->assertOk()
        ->assertResourcePagination();
});

test('Mark notification as read', function () {
    Event::dispatch(new Verified($this->user));
    $response = $this->actingAs($this->user)
        ->patch(route('profile-notification.update'));
    expect($response)
        ->status()->toBe(200)
        ->content()->toBeJson()
        ->json()->toHaveKeys(['data', 'message']);
});
