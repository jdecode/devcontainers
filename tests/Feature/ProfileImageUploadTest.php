<?php

use App\Enums\ProfileImageUploadStatusEnum;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->user = createUser();
});

afterEach(function () {
    Storage::deleteDirectory('users');
});

test('Upload and delete profile image', function () {
    $this->actingAs($this->user)
        ->post(route('profile-image.store'), [
            'file' => UploadedFile::fake()->image('photo1.jpg')
        ])
        ->assertAccepted()
        ->assertJsonStructure([
            'data',
            'message'
        ]);
    $path = config('constants.user.profile_image.image_params.path');
    $thumbnailPath = config('constants.user.profile_image.thumbnail_params.path');
    $filename = $this->user->image_filename;

    Storage::assertExists($path . $filename);
    Storage::assertExists($thumbnailPath . $filename);

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

test('Image status should return null', function () {
    $this->actingAs($this->user)
        ->get(route('profile-image.status'))
        ->assertOk()
        ->assertJson(['data' => ['status' => null], 'message' => '']);
});

test('Image status should return processing', function () {
    $this->actingAs($this->user)
        ->post(route('profile-image.store'), [
            'file' => UploadedFile::fake()->image('photo1.jpg')
        ]);

    $response = $this->actingAs($this->user)
        ->get(route('profile-image.status'))
        ->assertOk();
    $json = $response->decodeResponseJson();
    expect($json['data']['status'])->toBe(ProfileImageUploadStatusEnum::PROCESSING->value);
});
