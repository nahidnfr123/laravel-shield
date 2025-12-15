<?php

namespace NahidFerdous\Shield\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use NahidFerdous\Shield\Models\Permission;
use NahidFerdous\Shield\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // php artisan db:seed --class=PermissionSeeder

        $permissions = new PermissionsData;
        foreach ($permissions->permissionGroups() as $key => $permissionGroup) {
            foreach ($permissionGroup as $permission) {
                Permission::updateOrCreate([
                    'name' => is_string($permission) ? $permission : $permission[0],
                    'guard_name' => shieldDefaultGuard(),
                ], [
                    'slug' => Str::slug(is_string($permission) ? $permission : $permission[0]),
                    'type' => $key,
                    // 'special' => is_array($permission) ? $permission[1] : 0,
                ]);
            }
        }

        $superUser = config('shield.super_user_role');
        if ($superUser) {
            Role::where('name', $superUser)->first()?->syncPermissions(Permission::all());
        }
    }
}
