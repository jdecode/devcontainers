<?php

beforeEach(function () {
    $this->user = createUser();
});

test('Get profile', function () {
    $this->actingAs($this->user)
        ->get(route('profile.update', $this->user))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'first_name',
                'last_name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
        ]);
});

test('Update profile', function () {
    $this->actingAs($this->user)
        ->patch(route('profile.update'), ['first_name' => 'Test123',])
        ->assertOk()
        ->assertSee(__('messages.profile.updated'))
        ->assertJsonStructure(
            [
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ]
        );

    $this->assertDatabaseHas('users', [
        'first_name' => 'Test123',
    ]);
});

test('Update profile email', function () {
    $this->actingAs($this->user)
        ->patch(route('profile.update'), ['email' => 'test123@founderandlightning.com'])
        ->assertOk()
        ->assertSee(__('messages.profile.updated'))
        ->assertJsonStructure(
            [
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ]
        );

    $this->assertDatabaseHas('users', [
        'email' => 'test123@founderandlightning.com',
        'email_verified_at' => null
    ]);
});

test('Delete profile', function () {
    $this->actingAs($this->user)
        ->delete(route('profile.destroy'))
        ->assertOk()
        ->assertSee(__('messages.profile.deleted'));
    $this->assertDatabaseMissing('users', [
        'id' => $this->user->id,
    ]);
});
