<?php

use App\Models\User;

test('Get profile', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('profile.update', $user))
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
    $user = User::factory()->create();
    $this->actingAs($user)
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
    $user = User::factory()->create();
    $this->actingAs($user)
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
    $user = User::factory()->create();
    $this->actingAs($user)
        ->delete(route('profile.destroy'))
        ->assertOk()
        ->assertSee(__('messages.profile.deleted'));
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});
