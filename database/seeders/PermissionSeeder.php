<?php

namespace Database\Seeders;

use Config;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['name' => Config::get('constants.roles.super_admin')]);
        $userRole = Role::updateOrCreate(['name' => Config::get('constants.roles.user')]);

        $permissions = [
            // user
            'create user',
            'delete user',
            'update user',
            'view user',
            'view users',
            // dashboard
            'view dashboard',
            // logs
            'view logs',
            // roles
            'view roles',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission]);
        }
        $userRole->givePermissionTo('view users');
    }
}
