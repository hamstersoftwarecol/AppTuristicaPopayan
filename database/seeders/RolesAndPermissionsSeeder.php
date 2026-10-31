<?php

namespace Database\Seeders;

use App\Enums\Role as AppRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AppRole::cases() as $role) {
            foreach ($role->permissions() as $permission) {
                Permission::findOrCreate($permission, 'web');
            }

            Role::findOrCreate($role->value, 'web')->syncPermissions($role->permissions());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
