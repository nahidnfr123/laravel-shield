<?php

namespace NahidFerdous\Shield\Exceptions;

use RuntimeException;

/**
 * Thrown when a required package is not installed
 */
class PackageNotInstalledException extends RuntimeException
{
    protected string $driver;

    protected string $package;

    protected string $installCommand;

    public function __construct(string $driver, string $package, string $installCommand)
    {
        $this->driver = $driver;
        $this->package = $package;
        $this->installCommand = $installCommand;

        parent::__construct(
            "The '{$package}' package is required to use the '{$driver}' auth driver. ".
            "Please install it by running: {$installCommand}"
        );
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getPackage(): string
    {
        return $this->package;
    }

    public function getInstallCommand(): string
    {
        return $this->installCommand;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Package Not Installed',
                'message' => $this->getMessage(),
                'driver' => $this->driver,
                'package' => $this->package,
                'install_command' => $this->installCommand,
            ], 500);
        }

        return response()->view('errors.500', [
            'exception' => $this,
        ], 500);
    }
}

/**
 * Thrown when auth driver configuration is invalid
 */
class InvalidAuthDriverException extends RuntimeException
{
    protected string $driver;

    protected array $supportedDrivers;

    public function __construct(string $driver, array $supportedDrivers = ['sanctum', 'passport', 'jwt'])
    {
        $this->driver = $driver;
        $this->supportedDrivers = $supportedDrivers;

        parent::__construct(
            "Invalid auth driver '{$driver}' configured. ".
            'Supported drivers are: '.implode(', ', $supportedDrivers)
        );
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getSupportedDrivers(): array
    {
        return $this->supportedDrivers;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Invalid Auth Driver',
                'message' => $this->getMessage(),
                'driver' => $this->driver,
                'supported_drivers' => $this->supportedDrivers,
            ], 500);
        }

        return response()->view('errors.500', [
            'exception' => $this,
        ], 500);
    }
}
