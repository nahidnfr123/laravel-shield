<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Guard Support
    |--------------------------------------------------------------------------
    |
    | Enable multi-guard authentication to support multiple authentication
    | contexts (e.g., users, admins, customers) with separate token namespaces.
    |
    | When enabled, you can have different authentication guards for different
    | user types, each with their own tokens and permissions.
    |
    | Default: false
    |
    */
    'multi-guard' => true,

    /*
    |--------------------------------------------------------------------------
    | Disable Artisan Commands
    |--------------------------------------------------------------------------
    |
    | Set to true to disable Shield's artisan commands if you don't need them
    | in production or want to prevent accidental execution.
    |
    | Available commands:
    | - php artisan shield:check (validates package configuration)
    | - php artisan shield:install (installs Shield)
    |
    | Default: false
    |
    */
    'disable_commands' => false,

    /*
    |--------------------------------------------------------------------------
    | Authentication Driver
    |--------------------------------------------------------------------------
    |
    | Specify which authentication driver to use. Shield supports three drivers:
    |
    | - 'sanctum' (Laravel Sanctum - token-based auth, recommended for most apps)
    | Requires: composer require laravel/sanctum
    |
    | - 'passport' (Laravel Passport - OAuth2 authentication)
    | Requires: composer require laravel/passport
    |
    | - 'jwt' (JSON Web Token authentication using tymon/jwt-auth)
    | Requires: composer require tymon/jwt-auth
    |
    | Note: The corresponding package must be installed for the driver to work.
    | Run `php artisan shield:check` to verify your setup.
    |
    | Supported values: 'sanctum', 'passport', 'jwt'
    | Default: 'sanctum'
    |
    */
    'auth_driver' => 'jwt',

    /*
    |--------------------------------------------------------------------------
    | Default Guard
    |--------------------------------------------------------------------------
    |
    | The default guard to use when no guard is explicitly specified or detected.
    | This should match one of the guards defined in config/auth.php.
    |
    | Commonly used values: 'web', 'api'
    | Default: 'api'
    |
    */
    'default_guard' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Available Guards
    |--------------------------------------------------------------------------
    |
    | Define available guards and their URL prefixes for multi-guard setup.
    | Only applies when 'multi-guard' is enabled.
    |
    | Format: 'guard_name' => 'url_prefix'
    |
    | Example:
    | 'available_guards' => [
    |     'api' => 'user',      // Routes: /auth/user/login, /user/logout
    |     'admin' => 'admin',   // Routes: /auth/admin/login, /admin/logout
    |     'customer' => 'customer', // Routes: /auth/customer/login, /customer/logout
    | ]
    |
    | Make sure each guard is also defined in config/auth.php:
    | 'guards' => [
    |     'api' => ['driver' => 'sanctum', 'provider' => 'users'],
    |     'admin' => ['driver' => 'sanctum', 'provider' => 'admins'],
    | ]
    |
    */
    'available_guards' => [
        'api' => 'user',
        'admin' => 'admin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Generate Separate Routes for Guards
    |--------------------------------------------------------------------------
    |
    | When enabled, Shield will generate separate route groups for each guard
    | with their respective URL prefixes.
    |
    | Enabled (true):
    |   POST /api/auth/user/login    (guard: api)
    |   POST /api/auth/admin/login   (guard: admin)
    |
    | Disabled (false):
    |   POST /api/auth/login?guard=admin
    |   POST /api/auth/login?guard=user
    |
    | Separate routes are cleaner, more RESTful, and easier to secure.
    |
    | Default: true
    |
    */
    'generate_separate_route_for_guards' => true,

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all Shield routes.
    |
    | Example with 'api' prefix:
    | - POST /api/auth/login
    | - POST /api/auth/register
    |
    | Example with 'v1' prefix:
    | - POST /v1/auth/login
    | - POST /v1/auth/register
    |
    | Default: 'api'
    |
    */
    'route_prefix' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Route Name Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix for all route names to avoid conflicts with other packages.
    |
    | Example with 'shield.' prefix:
    | - Route name: shield.login
    | - Route name: shield.register
    |
    | Default: 'shield.'
    |
    */
    'route_name_prefix' => 'shield.',

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to all Shield routes.
    |
    | Common middleware:
    | - 'api' (for API routes)
    | - 'web' (for web routes)
    | - 'throttle:60,1' (rate limiting)
    |
    | Default: ['api']
    |
    */
    'route_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Load Default Routes
    |--------------------------------------------------------------------------
    |
    | Whether to automatically load Shield's default routes.
    |
    | Set to false if you want to manually define routes in your application.
    |
    | Default: true
    |
    */
    'load_default_routes' => true,

    /*
    |--------------------------------------------------------------------------
    | Disable API
    |--------------------------------------------------------------------------
    |
    | Set to true to completely disable Shield's API routes.
    | Useful if you only want to use Shield's services programmatically.
    |
    | Default: false
    |
    */
    'disable_api' => false,

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    */
    'auth' => [
        /*
        | Throttle Attempts
        |-----------------------------------------------------------------------
        | Maximum number of login attempts allowed per minute.
        | Helps prevent brute force attacks.
        |
        | Default: 6
        */
        'throttle_attempts' => 6,

        /*
        | Check Email Verification
        |-----------------------------------------------------------------------
        | When enabled, users must verify their email before they can login.
        |
        | Requires 'email_verified_at' column in your users table.
        |
        | Default: false
        */
        'check_verified' => false,

        /*
        | User Creation Configuration
        |-----------------------------------------------------------------------
        */
        'create_user' => [
            /*
            | Request Validation Class
            |-------------------------------------------------------------------
            | Custom form request class for user registration validation.
            | You can create your own class extending FormRequest.
            */
            'request_class' => \NahidFerdous\Shield\Http\Requests\ShieldUserCreateRequest::class,

            /*
            | Send Verification Email
            |-------------------------------------------------------------------
            | Automatically send verification email after user registration.
            | Only applies when 'check_verified' is enabled.
            */
            'send_verification_email' => true,
        ],

        /*
        | Login Configuration
        |-----------------------------------------------------------------------
        */
        'login' => [
            /*
            | Request Validation Class
            |-------------------------------------------------------------------
            | Custom form request class for login validation.
            */
            'request_class' => \NahidFerdous\Shield\Http\Requests\ShieldLoginRequest::class,

            /*
            | Credential Field
            |-------------------------------------------------------------------
            | Field(s) to use for user authentication.
            |
            | Single field: 'email' or 'username'
            | Multiple fields: 'email|username' or 'email|phone|username'
            |
            | When multiple fields are specified, Shield will try each field
            | until a matching user is found.
            |
            | Default: 'email'
            */
            'credential_field' => 'email',

            /*
            | Verification Field
            |-------------------------------------------------------------------
            | Database column to check for email verification status.
            | Make sure this field exists in your user model/table.
            |
            | Default: 'email_verified_at'
            */
            'verification_field' => 'email_verified_at',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default User Role
    |--------------------------------------------------------------------------
    |
    | The role slug to automatically assign to newly registered users.
    |
    | Make sure this role exists in your roles table.
    |
    | Default: 'user'
    |
    */
    'default_user_role_slug' => 'user',

    /*
    |--------------------------------------------------------------------------
    | Protected Role Slugs
    |--------------------------------------------------------------------------
    |
    | Roles that cannot be deleted or modified through the API.
    | Prevents accidental deletion of critical system roles.
    |
    | Default: ['admin', 'super-admin']
    |
    */
    'protected_role_slugs' => ['admin', 'super-admin'],

    /*
    |--------------------------------------------------------------------------
    | Delete Previous Access Tokens on Login
    |--------------------------------------------------------------------------
    |
    | When enabled, all existing tokens for a user will be revoked when they
    | log in, ensuring only one active session per user.
    |
    | Recommended for high-security applications.
    |
    | Default: false
    |
    */
    'delete_previous_access_tokens_on_login' => false,

    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    |
    | Configure email notifications sent by Shield.
    |
    */
    'emails' => [
        /*
        | Email Verification
        |-----------------------------------------------------------------------
        */
        'verify_email' => [
            /*
            | Email Subject
            |-------------------------------------------------------------------
            | Subject line for verification emails.
            */
            'subject' => 'Verify Email Address',

            /*
            | Email Template
            |-------------------------------------------------------------------
            | Custom blade template for verification email.
            | Set to null to use Shield's default template.
            |
            | Example: 'emails.verify-email'
            */
            'template' => null,

            /*
            | Token Expiration
            |-------------------------------------------------------------------
            | How long verification tokens remain valid (in hours).
            |
            | Default: 6 hours
            */
            'expiration' => 6,

            /*
            | Redirect URL
            |-------------------------------------------------------------------
            | Frontend URL where users will be redirected after clicking
            | the verification link in the email.
            |
            | The token will be appended as a query parameter: ?token=xxx
            |
            | Example: https://yourapp.com/verify-email
            */
            'redirect_url' => env('APP_URL').'/verify-email',
        ],

        /*
        | Password Reset
        |-----------------------------------------------------------------------
        */
        'reset_password' => [
            /*
            | Email Subject
            */
            'subject' => 'Reset Password Notification',

            /*
            | Email Template
            |-------------------------------------------------------------------
            | Custom blade template for password reset email.
            | Set to null to use Shield's default template.
            */
            'template' => null,

            /*
            | Token Expiration (in hours)
            */
            'expiration' => 6,

            /*
            | Redirect URL
            |-------------------------------------------------------------------
            | Frontend URL for password reset form.
            | The token will be appended as a query parameter.
            */
            'redirect_url' => env('APP_URL').'/reset-password',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Login Configuration
    |--------------------------------------------------------------------------
    |
    | Configure third-party OAuth providers for social authentication.
    |
    */
    'social' => [
        /*
        | Enable Social Login
        |-----------------------------------------------------------------------
        | Master switch for social authentication features.
        */
        'enabled' => env('SHIELD_SOCIAL_LOGIN_ENABLED', false),

        /*
        | Redirect URL
        |-----------------------------------------------------------------------
        | Frontend URL where users will be redirected after social authentication.
        */
        'redirect_url' => env('SHIELD_SOCIAL_REDIRECT_URL', '/auth/callback'),

        /*
        | OAuth Providers
        |-----------------------------------------------------------------------
        | Configuration for each supported OAuth provider.
        |
        | To enable a provider:
        | 1. Set 'enabled' to true
        | 2. Add credentials to your .env file
        | 3. Configure OAuth app in provider's developer console
        */
        'providers' => [
            'google' => [
                'enabled' => env('GOOGLE_LOGIN_ENABLED', false),
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect' => env('GOOGLE_REDIRECT_URL'),
            ],
            'facebook' => [
                'enabled' => env('FACEBOOK_LOGIN_ENABLED', false),
                'client_id' => env('FACEBOOK_CLIENT_ID'),
                'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
                'redirect' => env('FACEBOOK_REDIRECT_URL'),
            ],
            'github' => [
                'enabled' => env('GITHUB_LOGIN_ENABLED', false),
                'client_id' => env('GITHUB_CLIENT_ID'),
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'redirect' => env('GITHUB_REDIRECT_URL'),
            ],
            'twitter' => [
                'enabled' => env('TWITTER_LOGIN_ENABLED', false),
                'client_id' => env('TWITTER_CLIENT_ID'),
                'client_secret' => env('TWITTER_CLIENT_SECRET'),
                'redirect' => env('TWITTER_REDIRECT_URL'),
            ],
            'linkedin' => [
                'enabled' => env('LINKEDIN_LOGIN_ENABLED', false),
                'client_id' => env('LINKEDIN_CLIENT_ID'),
                'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
                'redirect' => env('LINKEDIN_REDIRECT_URL'),
            ],
        ],

        /*
        | Auto-Create User
        |-----------------------------------------------------------------------
        | Automatically create a new user account if the social login email
        | doesn't exist in the database.
        |
        | If false, social login will fail for new users.
        |
        | Default: true
        */
        'auto_create_user' => env('SHIELD_SOCIAL_AUTO_CREATE_USER', true),

        /*
        | Auto-Verify Email
        |-----------------------------------------------------------------------
        | Automatically mark email as verified for social logins, since the
        | OAuth provider has already verified the email.
        |
        | Default: true
        */
        'auto_verify_email' => env('SHIELD_SOCIAL_AUTO_VERIFY_EMAIL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for Shield's database queries to improve performance.
    |
    */
    'cache' => [
        /*
        | Enable Cache
        |-----------------------------------------------------------------------
        | Toggle caching on/off. Disable in development for easier debugging.
        |
        | Default: true
        */
        'enabled' => env('SHIELD_CACHE_ENABLED', true),

        /*
        | Cache Store
        |-----------------------------------------------------------------------
        | Cache driver to use. Must match a store defined in config/cache.php.
        |
        | Common stores: 'redis', 'memcached', 'file', 'database'
        | Set to null to use the default cache store.
        |
        | Default: null (uses default cache driver)
        */
        'store' => env('SHIELD_CACHE_STORE'),

        /*
        | Cache TTL (Time To Live)
        |-----------------------------------------------------------------------
        | How long to cache data (in seconds).
        |
        | Default: 300 seconds (5 minutes)
        */
        'ttl' => env('SHIELD_CACHE_TTL', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Abilities / Permissions
    |--------------------------------------------------------------------------
    |
    | Define permission abilities and which roles have access to them.
    |
    | This is used for Sanctum's ability-based token authentication.
    |
    | Format:
    | 'ability_name' => ['role1', 'role2']
    |
    | Example usage in routes:
    | Route::middleware('auth:sanctum', 'abilities:admin')
    |
    | Note: For JWT, this is used for custom claims validation.
    |
    */
    'abilities' => [
        'admin' => ['admin', 'super-admin'],
        'user_update' => ['admin', 'super-admin', 'user'],
    ],

    /*
    |--------------------------------------------------------------------------
    | JWT Configuration (only used when auth_driver is 'jwt')
    |--------------------------------------------------------------------------
    |
    | Configuration specific to JWT authentication.
    | Only applies when 'auth_driver' is set to 'jwt'.
    |
    */
    'jwt' => [
        /*
        | Token TTL (Time To Live)
        |-----------------------------------------------------------------------
        | How long JWT tokens remain valid (in minutes).
        |
        | Default: 60 minutes (1 hour)
        */
        'ttl' => env('SHIELD_JWT_TTL', 60),

        /*
        | Refresh Token TTL
        |-----------------------------------------------------------------------
        | How long refresh tokens remain valid (in minutes).
        |
        | Default: 20160 minutes (2 weeks)
        */
        'refresh_ttl' => env('SHIELD_JWT_REFRESH_TTL', 20160),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validate Configuration on Boot
    |--------------------------------------------------------------------------
    |
    | When enabled, Shield will validate the auth driver configuration when
    | the service provider boots. If validation fails, the error will be
    | logged but won't crash your application.
    |
    | Recommended: Enable in development, disable in production
    |
    | Default: false
    */
    'validate_on_boot' => env('SHIELD_VALIDATE_ON_BOOT', false),
];
