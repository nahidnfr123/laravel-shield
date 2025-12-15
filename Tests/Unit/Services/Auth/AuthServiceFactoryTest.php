<?php

namespace tests\Unit\Services\Auth;

use NahidFerdous\Shield\Exceptions\InvalidAuthDriverException;
use NahidFerdous\Shield\Exceptions\PackageNotInstalledException;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;
use NahidFerdous\Shield\Services\Auth\JWTAuthService;
use NahidFerdous\Shield\Services\Auth\PassportAuthService;
use NahidFerdous\Shield\Services\Auth\SanctumAuthService;
use Tests\TestCase;

class AuthServiceFactoryTest extends TestCase
{
    /**
     * Test that Sanctum auth service is created when configured
     */
    public function test_creates_sanctum_service_when_configured(): void
    {
        config(['shield.auth_driver' => 'sanctum']);

        $service = AuthServiceFactory::make();

        $this->assertInstanceOf(SanctumAuthService::class, $service);
    }

    /**
     * Test that Passport auth service is created when configured
     */
    public function test_creates_passport_service_when_configured(): void
    {
        if (! class_exists('Laravel\Passport\Passport')) {
            $this->markTestSkipped('Passport is not installed');
        }

        config(['shield.auth_driver' => 'passport']);

        $service = AuthServiceFactory::make();

        $this->assertInstanceOf(PassportAuthService::class, $service);
    }

    /**
     * Test that JWT auth service is created when configured
     */
    public function test_creates_jwt_service_when_configured(): void
    {
        if (! class_exists('Tymon\JWTAuth\Facades\JWTAuth')) {
            $this->markTestSkipped('JWT Auth is not installed');
        }

        config(['shield.auth_driver' => 'jwt']);

        $service = AuthServiceFactory::make();

        $this->assertInstanceOf(JWTAuthService::class, $service);
    }

    /**
     * Test that exception is thrown for invalid driver
     */
    public function test_throws_exception_for_invalid_driver(): void
    {
        $this->expectException(InvalidAuthDriverException::class);
        $this->expectExceptionMessage("Invalid auth driver 'invalid-driver'");

        AuthServiceFactory::make('invalid-driver');
    }

    /**
     * Test that exception is thrown when required package is not installed
     */
    public function test_throws_exception_when_package_not_installed(): void
    {
        // This test assumes Passport is NOT installed
        if (class_exists('Laravel\Passport\Passport')) {
            $this->markTestSkipped('Passport is installed, cannot test missing package scenario');
        }

        config(['shield.auth_driver' => 'passport']);

        $this->expectException(PackageNotInstalledException::class);
        $this->expectExceptionMessage('laravel/passport');

        AuthServiceFactory::make();
    }

    /**
     * Test isPackageInstalled method
     */
    public function test_checks_if_package_is_installed(): void
    {
        // Sanctum should be installed for tests
        $this->assertTrue(AuthServiceFactory::isPackageInstalled('sanctum'));

        // Check others based on actual installation
        $passportInstalled = class_exists('Laravel\Passport\Passport');
        $this->assertEquals($passportInstalled, AuthServiceFactory::isPackageInstalled('passport'));

        $jwtInstalled = class_exists('Tymon\JWTAuth\Facades\JWTAuth');
        $this->assertEquals($jwtInstalled, AuthServiceFactory::isPackageInstalled('jwt'));
    }

    /**
     * Test getAvailableDrivers method
     */
    public function test_returns_available_drivers(): void
    {
        $available = AuthServiceFactory::getAvailableDrivers();

        $this->assertIsArray($available);
        $this->assertContains('sanctum', $available); // Sanctum should always be available in tests

        // Other drivers depend on what's installed
        if (class_exists('Laravel\Passport\Passport')) {
            $this->assertContains('passport', $available);
        }

        if (class_exists('Tymon\JWTAuth\Facades\JWTAuth')) {
            $this->assertContains('jwt', $available);
        }
    }

    /**
     * Test getInstallationInstructions method
     */
    public function test_returns_installation_instructions(): void
    {
        $sanctumInstructions = AuthServiceFactory::getInstallationInstructions('sanctum');
        $this->assertEquals('composer require laravel/sanctum', $sanctumInstructions);

        $passportInstructions = AuthServiceFactory::getInstallationInstructions('passport');
        $this->assertEquals('composer require laravel/passport', $passportInstructions);

        $jwtInstructions = AuthServiceFactory::getInstallationInstructions('jwt');
        $this->assertEquals('composer require tymon/jwt-auth', $jwtInstructions);
    }

    /**
     * Test validateConfiguration method
     */
    public function test_validates_configuration_successfully(): void
    {
        config(['shield.auth_driver' => 'sanctum']);

        // Should not throw exception
        $this->assertNull(AuthServiceFactory::validateConfiguration());
    }

    /**
     * Test validateConfiguration throws exception for invalid config
     */
    public function test_validate_configuration_throws_for_invalid_driver(): void
    {
        config(['shield.auth_driver' => 'invalid']);

        $this->expectException(InvalidAuthDriverException::class);

        AuthServiceFactory::validateConfiguration();
    }

    /**
     * Test that custom guard is passed to service
     */
    public function test_passes_custom_guard_to_service(): void
    {
        config(['shield.auth_driver' => 'sanctum']);

        $service = AuthServiceFactory::make(null, 'admin');

        $this->assertEquals('admin', $service->guard);
    }

    /**
     * Test PackageNotInstalledException properties
     */
    public function test_package_not_installed_exception_properties(): void
    {
        $exception = new PackageNotInstalledException(
            'passport',
            'laravel/passport',
            'composer require laravel/passport'
        );

        $this->assertEquals('passport', $exception->getDriver());
        $this->assertEquals('laravel/passport', $exception->getPackage());
        $this->assertEquals('composer require laravel/passport', $exception->getInstallCommand());
    }

    /**
     * Test InvalidAuthDriverException properties
     */
    public function test_invalid_driver_exception_properties(): void
    {
        $exception = new InvalidAuthDriverException('invalid-driver');

        $this->assertEquals('invalid-driver', $exception->getDriver());
        $this->assertIsArray($exception->getSupportedDrivers());
        $this->assertContains('sanctum', $exception->getSupportedDrivers());
    }
}
