<?php

use App\Http\Controllers\v1\ProfileController;
use App\Http\Controllers\v1\ProfileImageController;
use App\Http\Controllers\v1\ProfileSubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::controller(ProfileController::class)
        ->prefix('/profile')
        ->group(function () {
            Route::get('/', 'show')->name('profile.show');
            Route::patch('/', 'update')->name('profile.update');
            Route::delete('/', 'destroy')->name('profile.destroy');
        });
    Route::get('/profile/subscription', ProfileSubscriptionController::class)
        ->name('profile-subscription.show');
    Route::post('/profile/image', [ProfileImageController::class, 'store'])
        ->name('profile-image.store');
    Route::delete('/profile/image', [ProfileImageController::class, 'destroy'])
        ->name('profile-image.destroy');
    Route::get('/profile/image/status', [ProfileImageController::class, 'status'])
        ->name('profile-image.status');
});

Route::get('/', function () {
    return ['this-is-laravel-api' => app()->version()];
})->name('home.api');

require __DIR__ . '/auth.php';
