<?php

namespace NahidFerdous\Shield\Services\Auth;

use NahidFerdous\Shield\Exceptions\InvalidAuthDriverException;
use NahidFerdous\Shield\Exceptions\PackageNotInstalledException;

class AuthServiceFactory
{
    /**
     * Required packages for each auth driver
     */
    protected static array $requiredPackages = [
        'sanctum' => [
            'package' => 'laravel/sanctum',
            'class' => 'Laravel\Sanctum\Sanctum',
            'install' => 'composer require laravel/sanctum',
        ],
        'passport' => [
            'package' => 'laravel/passport',
            'class' => 'Laravel\Passport\Passport',
            'install' => 'composer require laravel/passport',
        ],
        'jwt' => [
            'package' => 'tymon/jwt-auth',
            'class' => 'Tymon\JWTAuth\Facades\JWTAuth',
            'install' => 'composer require tymon/jwt-auth',
        ],
    ];

    /**
     * Create an auth service instance based on config
     *
     * @throws InvalidAuthDriverException
     * @throws PackageNotInstalledException
     */
    public static function make(?string $driver = null, ?string $guard = null): AuthService
    {
        $driver = $driver ?? config('shield.auth_driver', 'sanctum');

        if (! $guard) {
            $guard = requestGuardResolver();
        }

        // Validate driver is supported
        if (! in_array($driver, ['sanctum', 'passport', 'jwt'])) {
            throw new InvalidAuthDriverException($driver);
        }

        // Check if required package is installed
        self::ensurePackageInstalled($driver);

        return match ($driver) {
            'sanctum' => new SanctumAuthService($guard),
            'passport' => new PassportAuthService($guard),
            'jwt' => new JWTAuthService($guard),
        };
    }

    /**
     * Ensure the required package for the driver is installed
     *
     * @throws PackageNotInstalledException
     */
    protected static function ensurePackageInstalled(string $driver): void
    {
        $requirement = self::$requiredPackages[$driver];

        if (! class_exists($requirement['class'])) {
            throw new PackageNotInstalledException(
                $driver,
                $requirement['package'],
                $requirement['install']
            );
        }
    }

    /**
     * Check if a specific driver's package is installed
     */
    public static function isPackageInstalled(string $driver): bool
    {
        if (! isset(self::$requiredPackages[$driver])) {
            return false;
        }

        return class_exists(self::$requiredPackages[$driver]['class']);
    }

    /**
     * Get available auth drivers based on installed packages
     */
    public static function getAvailableDrivers(): array
    {
        $available = [];

        foreach (self::$requiredPackages as $driver => $requirement) {
            if (class_exists($requirement['class'])) {
                $available[] = $driver;
            }
        }

        return $available;
    }

    /**
     * Get installation instructions for a driver
     */
    public static function getInstallationInstructions(string $driver): ?string
    {
        return self::$requiredPackages[$driver]['install'] ?? null;
    }

    /**
     * Validate current configuration
     *
     * @throws InvalidAuthDriverException
     * @throws PackageNotInstalledException
     */
    public static function validateConfiguration(): void
    {
        $driver = config('shield.auth_driver', 'sanctum');

        if (! isset(self::$requiredPackages[$driver])) {
            throw new InvalidAuthDriverException($driver);
        }

        self::ensurePackageInstalled($driver);
    }

    public static function password(): ShieldPasswordService
    {
        return app(ShieldPasswordService::class);
    }

    public static function verification(): ShieldVerificationService
    {
        return app(ShieldVerificationService::class);
    }
}
