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
