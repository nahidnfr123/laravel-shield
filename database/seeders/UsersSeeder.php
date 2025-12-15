<?php

namespace NahidFerdous\Shield\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $userClass = resolveAuthenticatableClass();

        /** @var Model $user */
        $user = $userClass::updateOrCreate([
            'email' => 'admin@shield.project',
        ], [
            'password' => Hash::make('shield'),
            'name' => 'Shield Admin',
        ]);

        if (method_exists($user, 'assignRole')) {
            $user->assignRole('super-admin');
        }
    }
}
