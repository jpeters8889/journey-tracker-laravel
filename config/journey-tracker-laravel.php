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

    'confirm-endpoint' => 'journey-tracker-api/confirm',

    'visit-threshold-minutes' => 15,

    'queue' => env('JOURNEY_TRACKER_QUEUE'),
];
