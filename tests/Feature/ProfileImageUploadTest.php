<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
    $this->user = createUser();
});

afterEach(function () {
    Storage::deleteDirectory('users');
});

test('Upload and delete profile image', function () {
    $response = $this->actingAs($this->user)
        ->post(route('profile-image.store'), [
            'file' => UploadedFile::fake()->image('photo1.jpg')
        ])
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'image',
                'thumbnail'
            ],
            'message'
        ]);
    $data = $response->decodeResponseJson();
    Storage::assertExists($data['data']['image']);
    Storage::assertExists($data['data']['thumbnail']);

    $this->actingAs($this->user)
        ->delete(route('profile-image.destroy'))
        ->assertOk();
    Storage::assertDirectoryEmpty(config('filesystems.disks.test.root') . '/users');
});

test('Cannot upload file as no verified user', function () {
    $this->user->update(['email_verified_at' => null]);
    $this->actingAs($this->user)
        ->post(route('profile-image.store'), [
            'file' => UploadedFile::fake()->image('photo1.jpg')
        ])->assertForbidden();
});

test('Cannot upload file bigger than 10 MB', function () {
    $this->actingAs($this->user)
        ->post(route('profile-image.store'), [
            'file' => UploadedFile::fake()->image('photo1.jpg')->size(11000)
        ])->assertInvalid();
});

test('Cannot upload not image file', function () {
    $this->actingAs($this->user)
        ->post(route('profile-image.store'), [
            'file' => UploadedFile::fake()->create('sample.pdf', 1000, 'application/pdf')
        ])->assertInvalid();
});
