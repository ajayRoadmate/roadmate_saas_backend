<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'error_codes' => [
        'PHONE_NUMBER_NOT_REGISTERED' => ['code' => 1, 'message' =>  'Phone number is not registered with us'],
        'OTP_NOT_SENT' => ['code' => 2, 'message' =>  'Failed to send otp to the user'],
        'OTP_INVALID' => ['code' => 3, 'message' =>  'Invalid OTP'],
        'TOKEN_NOT_SAVED' => ['code' => 4, 'message' =>  'Failed to save the API token in the database'],
        'VALIDATION_FAILED' => ['code' => 5, 'message' =>  'Validation error, Please make sure that the request parameters are correct'],
        'FILE_UPLOAD_FAILED' => ['code' => 6, 'message' =>  'Failed to upload file into the server'],
        'UPDATE_FAILED' => ['code' => 7, 'message' =>  'Failed to update data in the server'],
        'DELETE_FAILED' => ['code' => 8, 'message' =>  'Failed to delete data from the server'],
        'NOT_ACTIVE_USER' => ['code' => 10, 'message' =>  'Your account is inactive. Please contact the administrator.'],

        'CUSTOM_ERROR' => ['code' => 100, 'message' =>  'Custom error message'],
        'UNKNOWN_ERROR' => ['code' => 101, 'message' =>  'Could not complete the request due to an unknown error'],
        'DATA_NOT_FOUND' => ['code' => 404, 'message' =>  'Could not find the requested data in the server'],
        'INTERNAL_SERVER_ERROR' => ['code' => 500, 'message' =>  'Failed to complete the request, due to an error in the server']
    ],


    'admin' => [
        'PHONE_NUMBER' => 7012263580,
        'OTP' => 5252
    ],

    'app_secret' => '3nRbfdxiiGx9zV0EZBUjd4VgQ7YK0bEsYWYq2Fv1gdU',

    'executive_keys' => [
        'PRIMARY_KEY' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJyb2FkTWF0ZSIsImlhdCI6MTcxOTIyMzQ4Mywic3ViIjoidXNlcklkIn0.jZDoOVfUtN5344337RLKCKxfhLZu9uOxt6yxkCL8a3s'
    ],


    'aliases' => [
        'Image' => Intervention\Image\Facades\Image::class,
    ]


];
