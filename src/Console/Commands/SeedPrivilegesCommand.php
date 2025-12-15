<?php

namespace NahidFerdous\Shield\Console\Commands;

use NahidFerdous\Shield\Database\Seeders\PermissionSeeder;

class SeedPrivilegesCommand extends BaseShieldCommand
{
    protected $signature = 'shield:seed-permissions {--force : Skip confirmation even though this overwrites privileges and role mappings}';

    protected $description = 'Re-seed Shield\'s default privilege definitions and role assignments';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will overwrite existing privilege definitions and role mappings. Continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        /** @var PermissionSeeder $seeder */
        $seeder = $this->laravel->make(PermissionSeeder::class);
        $seeder->setContainer($this->laravel)->setCommand($this);
        $seeder->run();

        $this->info('Default Shield privileges and role mappings have been re-seeded.');

        return self::SUCCESS;
    }
}
