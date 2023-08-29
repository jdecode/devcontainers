<?php

beforeEach(function () {
    $this->user = createUser('user@example.com', 'Password@123');
});

test('users can authenticate using the login screen', function () {
    $this->post(route('login'), [
        'email' => $this->user->email,
        'password' => 'Password@123',
    ])
        ->assertJsonStructure(
            [
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'token',
                ],
            ]
        )
        ->assertOk()
        ->assertSee(__('messages.user.logged_in'));
    $this->assertAuthenticated();
});

test('Users can not authenticate with invalid password', function () {
    $this->post(route('login'), [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ]);
    $this->assertGuest();
});

test('Users can logout', function () {
    $response = $this->post(route('login'), [
        'email' => $this->user->email,
        'password' => 'Password@123',
    ]);
    $token = json_decode($response->content(), true)['data']['token'];
    $this->withHeaders([
        'Accept' => 'application/json',
        'Authorization' => 'Bearer ' . $token
    ])->post(route('logout'))
        ->assertOk()
        ->assertSee(__('messages.user.logged_out'));
});

test('User should be prevented from making too many attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);
        $this->assertGuest();
    }
    $this->post(route('login'), [
        'email' => $this->user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors();
});
