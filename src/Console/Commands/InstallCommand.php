<?php

namespace NahidFerdous\Shield\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Random\RandomException;
use Spatie\Permission\PermissionServiceProvider;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\select;

class InstallCommand extends Command
{
    protected $signature = 'shield:install
        {--force : Pass the --force flag to migrate}
        {--driver= : Choose authentication driver (sanctum, passport, jwt)}
        {--dry-run : Print the steps without executing install:api or migrate}';

    protected $description = 'Run install:api followed by migrate to bootstrap Shield\'s requirements';

    protected string $selectedDriver = 'sanctum';

    protected bool $multiGuard = false;

    protected string $defaultGuard = 'api';

    /**
     * @throws RandomException
     */
    public function handle(): int
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            $this->warn('⚠️  .env file not found - please create it first.');

            return self::FAILURE;
        }
        $this->info('🛡️  Installing Laravel Shield...');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('Dry run: skipped install:api and migrate.');

            return self::SUCCESS;
        }

        // Step 1: Select Authentication Driver
        $this->selectedDriver = $this->selectAuthDriver();
        $this->newLine();

        // Step 2: Select Multi-Guard Option
        $this->multiGuard = $this->selectMultiGuard();
        $this->newLine();

        // Step 1.5: Install driver-specific composer dependencies
        $this->installDriverDependencies($this->selectedDriver);

        // Step 2: Install Laravel Sanctum API
        if (! $this->runRequiredCommand('install:api')) {
            return self::FAILURE;
        }

        // Step 3.5: Driver-specific setup (Passport keys, JWT secret)
        $this->performDriverSetup($this->selectedDriver);

        // Step 3: Prepare a User model for the chosen driver
        if (! $this->runRequiredCommand('shield:prepare-user-model', ['--driver' => $this->selectedDriver])) {
            return self::FAILURE;
        }

        // Publish Spatie migrations
        $this->call('vendor:publish', [
            '--provider' => PermissionServiceProvider::class,
            '--force' => $this->option('force'),
        ]);

        // Step 4: Run migrations
        $this->newLine();
        //        if ($this->confirmWithSelect('Do you want to run the database migrations?', true)) {
        if ($this->choice('Do you want to run the database migrations?', ['yes', 'no'], 'yes') === 'yes') {
            $arguments = [];
            if ($this->option('force')) {
                $arguments['--force'] = true;
            }

            if (! $this->runRequiredCommand('migrate', $arguments)) {
                return self::FAILURE;
            }
            $this->newLine();
        }

        // Step 5: Seed Shield data
        $this->newLine();
        //        if ($this->confirmWithSelect('Seed Shield roles, privileges, and the bootstrap admin user now?', true)) {
        if ($this->choice('Seed Shield roles, privileges, and the bootstrap admin user now?', ['yes', 'no'], 'yes') === 'yes') {
            if (! $this->runRequiredCommand('shield:seed')) {
                return self::FAILURE;
            }
            $this->newLine();
        }

        // Step 6: Publish a global exception handler
        $this->newLine();
        //        if ($this->confirmWithSelect('Do you want to add/publish the global exception handler?', true)) {
        if ($this->choice('Do you want to add/publish the global exception handler?', ['yes', 'no'], 'yes') === 'yes') {
            if (! $this->runRequiredCommand('shield:publish-exceptions')) {
                return self::FAILURE;
            }
            $this->newLine();
        }

        // Step 7: Show a completion message
        $this->showCompletionMessage($this->selectedDriver);

        return self::SUCCESS;
    }

    /**
     * Step 1: Select Authentication Driver
     */
    protected function selectAuthDriver(): string
    {
        $this->info('📦 Step 1: Select Authentication Driver');
        $this->line('Shield supports three authentication drivers:');
        $this->line('  • Sanctum - Token-based auth (recommended for most apps)');
        $this->line('  • Passport - OAuth2 authentication');
        $this->line('  • JWT - JSON Web Token authentication');
        $this->newLine();

        $driver = select(
            label: 'Which authentication driver would you like to use?',
            options: [
                'sanctum' => 'Sanctum (Token-based, recommended)',
                'passport' => 'Passport (OAuth2)',
                'jwt' => 'JWT (JSON Web Token)',
            ],
            default: 'sanctum'
        );

        // Set the default guard based on a driver
        $this->defaultGuard = ($driver === 'sanctum') ? 'web' : 'api';

        $this->info("✓ Selected driver: $driver");

        // Update .env file and create/update config
        $this->updateEnvDriver($driver);
        $this->updateConfigFile($driver, null);

        return $driver;
    }

    /**
     * Step 2: Select Multi-Guard Option
     */
    protected function selectMultiGuard(): bool
    {
        $this->info('🔒 Step 2: Multi-Guard Configuration');
        $this->line('Multi-guard allows different authentication contexts (e.g., users, admins, customers)');
        $this->line('with separate token, user tables, namespaces and permissions.');
        $this->newLine();

        $choice = select(
            label: 'Do you want to enable multi-guard authentication?',
            options: ['yes' => 'Yes', 'no' => 'No'],
            default: 'no'
        );

        $multiGuard = ($choice === 'yes');

        $this->info('✓ Multi-guard: '.($multiGuard ? 'Enabled' : 'Disabled'));

        // Update config file with multi-guard setting
        $this->updateConfigFile($this->selectedDriver, $multiGuard);

        return $multiGuard;
    }

    /**
     * Helper method for yes/no confirmations with a select option
     */
    protected function confirmWithSelect(string $question, bool $default = true): bool
    {
        $choice = select(
            label: $question,
            options: ['yes' => 'Yes', 'no' => 'No'],
            default: $default ? 'yes' : 'no'
        );

        return $choice === 'yes';
    }

    /**
     * Update .env file with a selected driver
     */
    protected function updateEnvDriver(string $driver): void
    {
        $envPath = base_path('.env');
        $contents = file_get_contents($envPath);

        // Check if SHIELD_AUTH_DRIVER exists
        if (preg_match('/^SHIELD_AUTH_DRIVER=/m', $contents)) {
            // Update existing
            $updated = preg_replace(
                '/^SHIELD_AUTH_DRIVER=.*/m',
                "SHIELD_AUTH_DRIVER=$driver",
                $contents
            );
        } else {
            // Add new
            $updated = $contents;
            if (! str_ends_with($updated, "\n")) {
                $updated .= "\n";
            }
            $updated .= "\n# Shield Authentication Driver\nSHIELD_AUTH_DRIVER=$driver\n";
        }

        file_put_contents($envPath, $updated);
        $this->line("✓ Updated .env: SHIELD_AUTH_DRIVER=$driver");
        $this->newLine();
    }

    /**
     * Create or update a config file with driver and multi-guard settings
     */
    protected function updateConfigFile(string $driver, ?bool $multiGuard = null): void
    {
        $configPath = config_path('shield.php');

        // Publish config if it doesn't exist
        if (! file_exists($configPath)) {
            $this->call('vendor:publish', [
                '--tag' => 'shield-config',
                '--force' => false,
            ]);
        }

        // Read the config file
        $contents = file_get_contents($configPath);

        // Update auth_driver
        $contents = preg_replace(
            "/'auth_driver'\s*=>\s*'[^']*'/",
            "'auth_driver' => '$driver'",
            $contents
        );

        // Update default_guard based on a driver
        $defaultGuard = ($driver === 'sanctum') ? 'web' : 'api';
        $contents = preg_replace(
            "/'default_guard'\s*=>\s*'[^']*'/",
            "'default_guard' => '$defaultGuard'",
            $contents
        );

        // Update multi-guard if specified
        if ($multiGuard !== null) {
            $multiGuardValue = $multiGuard ? 'true' : 'false';
            $contents = preg_replace(
                "/'multi-guard'\s*=>\s*(true|false)/",
                "'multi-guard' => $multiGuardValue",
                $contents
            );
        }

        file_put_contents($configPath, $contents);
        $this->line('✓ Updated config/shield.php');
    }

    /**
     * Show a completion message with next steps
     */
    protected function showCompletionMessage(string $driver): void
    {
        $this->newLine();
        $this->info('✅ Shield installation complete!');
        $this->newLine();

        $this->line('📋 <fg=bright-white>What\'s been set up:</>');
        $this->line('   ✓ API routes configured');
        $this->line('   ✓ User model prepared with necessary traits');
        $this->line('   ✓ Database migrations run');
        $this->line('   ✓ Roles and privileges seeded');
        $this->line('   ✓ Admin user created');
        $this->newLine();

        $this->line('👤 <fg=bright-white>Default Admin Credentials:</>');
        $this->line('   Email: <fg=yellow>admin@example.com</>');
        $this->line('   Password: <fg=yellow>password</>');
        $this->warn('   ⚠ Please change the admin password immediately!');
        $this->newLine();

        // Driver-specific instructions
        $this->showDriverInstructions($driver);

        $this->line('📚 <fg=bright-white>Quick Commands:</>');
        $this->line('   • List users: <fg=cyan>php artisan shield:list-users</>');
        $this->line('   • Create user: <fg=cyan>php artisan shield:create-user</>');
        $this->line('   • Switch driver: <fg=cyan>php artisan shield:switch-driver [sanctum|passport|jwt]</>');
        $this->line('   • View all commands: <fg=cyan>php artisan shield</>');
        $this->newLine();

        $this->line('🚀 <fg=bright-white>Test Your API:</>');
        $this->line('   POST /api/login - Login and get token');
        $this->line('   GET  /api/me - Get authenticated user');
        $this->newLine();

        $this->info('Happy coding! 🛡️');
    }

    protected function installDriverDependencies(string $driver): void
    {
        $packages = [];

        if ($driver === 'passport') {
            $packages[] = 'laravel/passport:^12.0';
        } elseif ($driver === 'jwt') {
            $packages[] = 'tymon/jwt-auth:^2.2';
        }

        if (empty($packages)) {
            $this->info('No additional dependencies needed for this driver.');

            return;
        }

        foreach ($packages as $package) {
            $this->info("Installing $package via composer...");
            $this->runComposerRequire($package);
        }
    }

    protected function runComposerRequire(string $package): void
    {
        $process = Process::fromShellCommandline("composer require $package", base_path());
        $process->setTimeout(null); // optional, no timeout for long installations

        try {
            $process->mustRun(function ($type, $buffer) {
                echo $buffer; // stream output to the console
            });
        } catch (ProcessFailedException $exception) {
            $this->error("Failed to install $package: {$exception->getMessage()}");
        }
    }

    /**
     * Show driver-specific setup instructions
     */
    protected function showDriverInstructions(string $driver): void
    {
        $this->line('⚙️  <fg=bright-white>Authentication Driver: '.strtoupper($driver).'</>');
        $this->newLine();

        switch ($driver) {
            case 'sanctum':
                $this->line('   ✓ Sanctum is ready to use!');
                $this->line('   • Config file: <fg=cyan>config/sanctum.php</>');
                $this->line('   • Middleware: <fg=cyan>auth:sanctum</>');
                break;

            case 'passport':
                if ($this->passportKeysExist()) {
                    $this->line('   ✓ Passport keys generated successfully!');
                } else {
                    $this->warn('   ⚠ Passport keys not found!');
                    $this->line('   Run: <fg=cyan>php artisan passport:install</>');
                }
                $this->line('   • Don\'t forget to add Client ID and Secret to .env');
                $this->line('   • Middleware: <fg=cyan>auth:api</>');
                break;

            case 'jwt':
                $this->line('   ✓ JWT configuration added to .env');
                $this->line('   • Middleware: <fg=cyan>jwt.auth</>');
                break;
        }

        $this->newLine();
    }

    /**
     * Perform driver-specific setup
     */
    protected function performDriverSetup(string $driver): void
    {
        if ($driver === 'passport') {
            $this->setupPassport();
        } elseif ($driver === 'jwt') {
            $this->setupJWT();
        }
    }

    /**
     * Setup Passport
     */
    protected function setupPassport(): void
    {
        $this->newLine();

        if (! $this->passportKeysExist()) {
            $this->info('Passport requires encryption keys...');

            if ($this->confirmWithSelect('Generate Passport keys now?', true)) {
                $this->runRequiredCommand('passport:install', ['--uuids' => true]);
                $this->newLine();
                $this->warn('⚠️  IMPORTANT: Copy the Client ID and Secret from above to your .env file:');
                $this->line('   PASSPORT_PERSONAL_ACCESS_CLIENT_ID=<client-id>');
                $this->line('   PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=<client-secret>');
                $this->newLine();
            } else {
                $this->warn('⚠️  You must run "php artisan passport:install" before using Passport.');
            }
        } else {
            $this->info('✓ Passport keys already exist');
        }

        $this->newLine();
    }

    protected function setupJWT(): void
    {
        $this->newLine();
        // publish config/jwt.php
        $this->runRequiredCommand(
            'vendor:publish',
            [
                '--provider' => 'Tymon\JWTAuth\Providers\LaravelServiceProvider',
            ]
        );

        $this->info('Setting up JWT...');
        $this->runRequiredCommand('jwt:secret');

        $this->newLine();
    }

    /**
     * Check if Passport keys exist
     */
    protected function passportKeysExist(): bool
    {
        return file_exists(storage_path('oauth-private.key'))
            && file_exists(storage_path('oauth-public.key'));
    }

    /**
     * Run a required artisan command
     */
    protected function runRequiredCommand(string $command, array $arguments = []): bool
    {
        $this->info(sprintf('Running %s...', $command));

        try {
            $exitCode = Artisan::call($command, $arguments, $this->getOutput());
        } catch (CommandNotFoundException $e) {
            $this->error(sprintf('Command "%s" is not available in this application.', $command));

            return false;
        }

        if ($exitCode !== 0) {
            $this->error(sprintf('Command "%s" exited with code %s.', $command, $exitCode));

            return false;
        }

        return true;
    }
}
