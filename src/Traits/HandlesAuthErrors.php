<?php

namespace NahidFerdous\Shield\Traits;

use Illuminate\Http\JsonResponse;
use NahidFerdous\Shield\Exceptions\InvalidAuthDriverException;
use NahidFerdous\Shield\Exceptions\PackageNotInstalledException;

trait HandlesAuthErrors
{
    /**
     * Handle authentication exceptions and return appropriate responses
     */
    protected function handleAuthException(\Exception $e): JsonResponse
    {
        if ($e instanceof PackageNotInstalledException) {
            return $this->packageNotInstalledResponse($e);
        }

        if ($e instanceof InvalidAuthDriverException) {
            return $this->invalidDriverResponse($e);
        }

        if ($e instanceof \RuntimeException) {
            return $this->runtimeErrorResponse($e);
        }

        return $this->genericErrorResponse($e);
    }

    /**
     * Response for missing package
     */
    protected function packageNotInstalledResponse(PackageNotInstalledException $e): JsonResponse
    {
        return response()->json([
            'error' => 'Configuration Error',
            'message' => 'Authentication service is not properly configured.',
            'details' => config('app.debug') ? [
                'driver' => $e->getDriver(),
                'missing_package' => $e->getPackage(),
                'install_command' => $e->getInstallCommand(),
            ] : null,
        ], 500);
    }

    /**
     * Response for invalid driver
     */
    protected function invalidDriverResponse(InvalidAuthDriverException $e): JsonResponse
    {
        return response()->json([
            'error' => 'Configuration Error',
            'message' => 'Invalid authentication driver configured.',
            'details' => config('app.debug') ? [
                'driver' => $e->getDriver(),
                'supported_drivers' => $e->getSupportedDrivers(),
            ] : null,
        ], 500);
    }

    /**
     * Response for runtime errors (invalid credentials, user not verified, etc.)
     */
    protected function runtimeErrorResponse(\RuntimeException $e): JsonResponse
    {
        $statusCode = $e->getCode() ?: 400;

        // Map common auth error codes
        $statusCode = match ($statusCode) {
            401 => 401, // Unauthorized
            403 => 403, // Forbidden (not verified)
            423 => 423, // Locked (suspended)
            default => 400, // Bad Request
        };

        return response()->json([
            'error' => 'Authentication Failed',
            'message' => $e->getMessage(),
        ], $statusCode);
    }

    /**
     * Generic error response
     */
    protected function genericErrorResponse(\Exception $e): JsonResponse
    {
        return response()->json([
            'error' => 'Server Error',
            'message' => config('app.debug') ? $e->getMessage() : 'An error occurred.',
        ], 500);
    }
}
