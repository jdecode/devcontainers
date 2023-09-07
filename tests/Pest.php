<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Pest\Expectation;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

use function Pest\Laravel\assertAuthenticated;
use function PHPUnit\Framework\assertEquals;

uses(TestCase::class, RefreshDatabase::class)->beforeEach(function () {
    $this->seed();
})->in('./');

expect()->extend(
    'toBeAuthenticated',
    function (string $guard = null): Expectation {
        assertAuthenticated($guard);
        $authenticated = Auth::guard($guard)->user();

        assertEquals(
            $this->value->id,
            $authenticated->id,
            "The User ID #{$this->value->id} doesn't match authenticated User ID #{$authenticated->id}"
        );

        return $this;
    }
);

function createRawUser(string $email = '', string $password = ''): User
{
    return User::factory()->create([
        'email' => $email ?: fake()->email,
        'password' => Hash::make($password) ?: Hash::make(fake()->password()),
    ]);
}

function createUser(string $email = '', string $password = ''): User
{
    $userRole = config('constants.roles.user');
    return createRawUser($email, $password)->assignRole($userRole);
}

function createSuperAdmin(string $email = '', string $password = ''): User
{
    $superAdminRole = config('constants.roles.super_admin');
    return createRawUser($email, $password)->assignRole($superAdminRole);
}

function getRoleUser(): Role
{
    return Role::where('name', config('constants.roles.user'))->first();
}

TestResponse::macro('assertResourcePagination', function () {
    $this->assertJsonStructure([
        'data',
        'links' => [
            'first',
            'last',
            'prev',
            'next'
        ],
        'meta' => [
            'current_page',
            'from',
            'last_page',
            'links',
            'path',
            'per_page',
            'to',
            'total'
        ]
    ]);
});
