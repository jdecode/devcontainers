<?php

use Illuminate\Auth\Events\Verified;

beforeEach(function () {
    $this->user = createUser();
});

test('Get Profile notifications', function () {
    $response = $this->actingAs($this->user)
        ->get(route('profile-notification.show'));
    expect($response)
        ->status()->toBe(200)
        ->content()->toBeJson()
        ->json()->toHaveKeys([
            'current_page',
            'data',
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'links',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
            'unread_count',
        ]);
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
