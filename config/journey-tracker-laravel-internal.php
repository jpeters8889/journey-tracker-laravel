<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Internal configuration
|--------------------------------------------------------------------------
|
| Merged into the journey-tracker-laravel config at registration, but never
| published, so these keys stay out of the config an installing application
| sees. They exist to point the SDK somewhere other than production while
| working on it locally. Anything set in the published config file, or at
| runtime, takes precedence over the values here.
|
*/

return [
    'host' => env('JOURNEY_TRACKER_HOST', 'https://journey-tracker.cloud'),

    'verify-tls' => env('JOURNEY_TRACKER_VERIFY_TLS', true),
];
