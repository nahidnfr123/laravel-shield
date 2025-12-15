<?php

namespace NahidFerdous\Shield\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use NahidFerdous\Shield\Console\Commands\DocCommand;
use NahidFerdous\Shield\Console\Commands\InstallCommand;
use NahidFerdous\Shield\Console\Commands\PostmanCollectionCommand;
use NahidFerdous\Shield\Console\Commands\PrepareUserModelCommand;
use NahidFerdous\Shield\Console\Commands\PublishConfigCommand;
use NahidFerdous\Shield\Console\Commands\PublishExceptionHandlerCommand;
use NahidFerdous\Shield\Console\Commands\PublishMigrationsCommand;
use NahidFerdous\Shield\Console\Commands\SeedCommand;
use NahidFerdous\Shield\Console\Commands\SeedPrivilegesCommand;
use NahidFerdous\Shield\Console\Commands\SeedRolesCommand;
use NahidFerdous\Shield\Console\Commands\ShieldCheckCommand;
use NahidFerdous\Shield\Console\Commands\StarCommand;
use NahidFerdous\Shield\Console\Commands\SwitchAuthDriverCommand;
use NahidFerdous\Shield\Http\Middleware\DetectGuardFromRoute;
use NahidFerdous\Shield\Http\Middleware\JWTAuthenticate;
use NahidFerdous\Shield\Http\Middleware\PermissionOrSelf;
use NahidFerdous\Shield\Http\Requests\ShieldCreateUserRequest;
use NahidFerdous\Shield\Services\Auth\AuthServiceFactory;
use NahidFerdous\Shield\Services\SocialAuthService;

class ShieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/shield.php', 'shield');

        $this->app->register(ShieldEventServiceProvider::class);

        // Register Spatie's service provider
        $this->app->register(\Spatie\Permission\PermissionServiceProvider::class);

        $this->registerValidations();
        $this->registerServices();
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerBindings();
        $this->registerAuthDriver();
        $this->registerViews();
        $this->registerCommands();

        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
            $this->commands([
                ShieldCheckCommand::class,
            ]);
        }

        // Validate configuration in production (optional)
        if (! $this->app->environment('testing') && config('shield.validate_on_boot', false)) {
            try {
                AuthServiceFactory::validateConfiguration();
            } catch (\RuntimeException $e) {
                // Log the error instead of throwing to prevent app from crashing
                if ($this->app->bound('log')) {
                    logger()->error('Shield configuration error: '.$e->getMessage());
                }
            }
        }
    }

    protected function registerViews(): void
    {
        // Register shield views
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'shield');

        // Publish views
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/shield'),
        ], 'shield-views');
    }

    protected function registerValidations(): void
    {
        // Only bind if the user provided a custom request
        $createClass = config('shield.auth.create_user.request_class');
        if ($createClass && $createClass !== ShieldCreateUserRequest::class) {
            $this->app->bind(\NahidFerdous\Shield\Http\Requests\ShieldCreateUserRequest::class, $createClass);
        }
        $loginClass = config('shield.auth.login.request_class');
        if ($loginClass && $loginClass !== \NahidFerdous\Shield\Http\Requests\ShieldLoginRequest::class) {
            $this->app->bind(\NahidFerdous\Shield\Http\Requests\ShieldLoginRequest::class, $loginClass);
        }
    }

    protected function registerServices(): void
    {
        // Register Social Auth Service
        $this->app->singleton(SocialAuthService::class, fn () => new SocialAuthService);

        // Register Auth Service Factory
        $this->app->singleton('shield.auth', fn () => AuthServiceFactory::make());
    }

    protected function registerRoutes(): void
    {
        if (config('shield.disable_api', false) || ! config('shield.load_default_routes', true)) {
            return;
        }

        Route::group([
            'prefix' => trim(config('shield.route_prefix', 'api'), '/'),
            'middleware' => config('shield.route_middleware', ['api']),
            'as' => config('shield.route_name_prefix', 'shield.'),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../../routes/shield-api.php');
        });

        // Only load Passport routes if auth driver is passport and Passport is installed
        if (class_exists(Laravel\Passport\Passport::class) && config('shield.auth_driver') === 'passport') {
            Laravel\Passport\Passport::routes();
            Laravel\Passport\Passport::tokensCan([
                'admin' => 'Admin access',
                'user' => 'User access',
            ]);
            Laravel\Passport\Passport::setDefaultScope(['user']);
        }
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app['router'];

        // spatie middleware
        $router->aliasMiddleware('shield.role', \Spatie\Permission\Middleware\RoleMiddleware::class);
        $router->aliasMiddleware('shield.permission', \Spatie\Permission\Middleware\PermissionMiddleware::class);
        $router->aliasMiddleware('shield.role_or_permission', \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class);
        $router->aliasMiddleware('permission_or_self', PermissionOrSelf::class);
        $router->aliasMiddleware('shield.guard', DetectGuardFromRoute::class);

        $authDriver = config('shield.auth_driver', 'sanctum');

        $driver = config('shield.auth_driver', 'sanctum');
        if ($driver === 'sanctum') {
            $router->aliasMiddleware('ability', CheckForAnyAbility::class);
            $router->aliasMiddleware('abilities', CheckAbilities::class);
        } elseif ($driver === 'passport') {
            if (class_exists(\Laravel\Passport\Http\Middleware\CheckScopes::class)) {
                $router->aliasMiddleware('scopes', \Laravel\Passport\Http\Middleware\CheckScopes::class);
                $router->aliasMiddleware('scope', \Laravel\Passport\Http\Middleware\CheckForAnyScope::class);
            }
        } elseif ($driver === 'jwt') {
            $router->aliasMiddleware('jwt.auth', JWTAuthenticate::class);
        }
        // Sanctum/Passport ability middleware
        //        if ($authDriver === 'sanctum') {
        //            if (!array_key_exists('ability', $router->getMiddleware())) {
        //                $router->aliasMiddleware('ability', CheckForAnyAbility::class);
        //            }
        //
        //            if (!array_key_exists('abilities', $router->getMiddleware())) {
        //                $router->aliasMiddleware('abilities', CheckAbilities::class);
        //            }
        //        } elseif ($authDriver === 'passport') {
        //            if (!array_key_exists('abilities', $router->getMiddleware())) {
        //                $router->aliasMiddleware('scopes', \Laravel\Passport\Http\Middleware\CheckScopes::class);
        //            }
        //            if (!array_key_exists('ability', $router->getMiddleware())) {
        //                $router->aliasMiddleware('scope', \Laravel\Passport\Http\Middleware\CheckForAnyScope::class);
        //            }
        //        } elseif ($authDriver === 'jwt') {
        //            // JWT ability middleware
        //            if (!array_key_exists('ability', $router->getMiddleware())) {
        //                $router->aliasMiddleware('jwt.auth', JWTAuthenticate::class);
        //            }
        //        }
    }

    protected function registerBindings(): void
    {
        Route::bind('user', function ($value) {
            $userClass = resolveAuthenticatableClass();

            return $userClass::query()->findOrFail($value);
        });
    }

    protected function registerAuthDriver(): void
    {
        $driver = config('shield.auth_driver', 'sanctum');

        // Configure guards based on a driver
        if ($driver === 'jwt') {
            config(['auth.guards.api.driver' => 'jwt']);
            config(['shield.default_guard' => 'api']);
        } elseif ($driver === 'passport') {
            config(['auth.guards.api.driver' => 'passport']);
            config(['shield.default_guard' => 'api']);
        } else {
            config(['auth.guards.api.driver' => 'sanctum']);
            config(['shield.default_guard' => 'web']);
        }
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../../config/shield.php' => config_path('shield.php'),
        ], 'shield-config');

        $this->publishes([
            __DIR__.'/../../database/migrations/' => database_path('migrations'),
        ], 'shield-migrations');

        $this->publishes([
            __DIR__.'/../../database/seeders/' => database_path('seeders'),
            __DIR__.'/../../database/factories/' => database_path('factories'),
        ], 'shield-database');

        $this->publishes([
            __DIR__.'/../../resources/' => resource_path('vendor/shield'),
        ], 'shield-assets');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole() || config('shield.disable_commands', false)) {
            return;
        }

        $this->commands([
            DocCommand::class,
            InstallCommand::class,
            PublishExceptionHandlerCommand::class,
            PrepareUserModelCommand::class,
            SwitchAuthDriverCommand::class,
            PublishConfigCommand::class,
            PostmanCollectionCommand::class,
            PublishMigrationsCommand::class,
            SeedCommand::class,
            SeedPrivilegesCommand::class,
            SeedRolesCommand::class,
            StarCommand::class,
        ]);
    }
}
