<?php

namespace NahidFerdous\Shield\Console\Commands;

use NahidFerdous\Shield\Database\Seeders\RoleSeeder;

class SeedRolesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:seed-roles {--force : Skip confirmation even though this truncates the roles table}';

    protected $description = 'Re-seed the Shield roles list (truncates the roles table)';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will truncate the roles table before re-seeding. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        /** @var RoleSeeder $seeder */
        $seeder = $this->laravel->make(RoleSeeder::class);
        $seeder->run();

        $this->info('Default Shield roles have been re-seeded.');

        return self::SUCCESS;
    }
}
