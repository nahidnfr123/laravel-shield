<?php

namespace NahidFerdous\Shield\Database\Seeders;

use NahidFerdous\Shield\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder {
    public function run(): void {
        $userClass = config('shield.models.user', config('auth.providers.users.model', 'App\\Models\\User'));

        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user = $userClass::create([
            'email' => 'admin@shield.project',
            'password' => Hash::make('shield'),
            'name' => 'Shield Admin',
        ]);

        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $user->roles()->attach($adminRole);
        }
    }
}
