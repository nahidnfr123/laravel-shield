<?php

namespace NahidFerdous\Shield\Console\Commands;

use Illuminate\Console\Command;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;

class ShieldCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'shield:check';

    /**
     * The console command description.
     */
    protected $description = 'Check Shield package configuration and dependencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🛡️  Shield Package Configuration Check');
        $this->newLine();

        // Check configured driver
        $configuredDriver = config('shield.auth_driver', 'sanctum');
        $this->info("📋 Configured Auth Driver: <fg=yellow>{$configuredDriver}</>");
        $this->newLine();

        // Check available drivers
        $this->info('📦 Checking installed packages...');
        $this->newLine();

        $allDrivers = ['sanctum', 'passport', 'jwt'];
        $availableDrivers = AuthServiceFactory::getAvailableDrivers();

        foreach ($allDrivers as $driver) {
            $isInstalled = in_array($driver, $availableDrivers);
            $isConfigured = $driver === $configuredDriver;

            $status = $isInstalled ? '<fg=green>✓ Installed</>' : '<fg=red>✗ Not Installed</>';
            $marker = $isConfigured ? ' <fg=cyan>(ACTIVE)</>' : '';

            $this->line("  {$driver}: {$status}{$marker}");

            if (! $isInstalled && $isConfigured) {
                $installCmd = AuthServiceFactory::getInstallationInstructions($driver);
                $this->warn('    ⚠️  Required package not installed!');
                $this->line("    Run: <fg=yellow>{$installCmd}</>");
            }
        }

        $this->newLine();

        // Validate configuration
        try {
            AuthServiceFactory::validateConfiguration();
            $this->info('✅ Configuration is valid!');
            $this->newLine();

            // Show multi-guard status
            $multiGuard = config('shield.multi-guard', false);
            $this->info('🔒 Multi-Guard: '.($multiGuard ? '<fg=green>Enabled</>' : '<fg=yellow>Disabled</>'));

            // Show configured guards if multi-guard is enabled
            if ($multiGuard) {
                $guards = config('shield.guards', []);
                if (! empty($guards)) {
                    $this->info('   Guards: '.implode(', ', $guards));
                }
            }

            $this->newLine();

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Configuration Error:');
            $this->error('   '.$e->getMessage());
            $this->newLine();

            // Suggest fix
            if (! AuthServiceFactory::isPackageInstalled($configuredDriver)) {
                $installCmd = AuthServiceFactory::getInstallationInstructions($configuredDriver);
                $this->info('💡 To fix this issue, run:');
                $this->line("   <fg=yellow>{$installCmd}</>");
                $this->newLine();
            }

            return self::FAILURE;
        }
    }
}
