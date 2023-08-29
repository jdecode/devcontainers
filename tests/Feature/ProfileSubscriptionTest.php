<?php

beforeEach(function () {
    $this->user = createUser();
});

test('Get Profile subscription details if not subscribed', function () {

    $response = $this->actingAs($this->user)
        ->get(route('profile-subscription.show'));
    expect($response)
        ->status()->toBe(200)
        ->content()->toBeJson()
        ->json()->toHaveKeys(['data', 'message']);
});
