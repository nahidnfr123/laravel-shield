<?php

namespace NahidFerdous\Shield\Database\Seeders;

use Illuminate\Database\Seeder;
use NahidFerdous\Shield\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super-admin', 'admin'];
        collect($roles)->each(fn ($role) => Role::updateOrCreate(['name' => $role]));
    }
}
