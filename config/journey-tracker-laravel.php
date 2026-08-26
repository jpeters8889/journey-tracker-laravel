<?php

declare(strict_types=1);

return [
    'enabled' => env('JOURNEY_TRACKER_ENABLED', true),

    'app-token' => env('JOURNEY_TRACKER_TOKEN'),

    'dont-track' => [
        //
    ],

    'internal-event-endpoint' => 'journey-tracker-api/event',

    'heartbeat-endpoint' => 'journey-tracker-api/heartbeat',

    'host' => env('JOURNEY_TRACKER_HOST', 'https://journey-tracker.cloud'),

    'queue' => env('JOURNEY_TRACKER_QUEUE'),

    'verify-tls' => env('JOURNEY_TRACKER_VERIFY_TLS', true),
];
