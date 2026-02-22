<?php

return [
    'enabled' => env('JOURNEY_TRACKER_ENABLED', true),

    'app-token' => env('JOURNEY_TRACKER_TOKEN'),

    'dont-track' => [
        //
    ],

    'internal-event-endpoint' => 'journey-tracker-api/event',

    'host' => env('JOURNEY_TRACKER_HOST', 'https://journey-tracker.cloud'),
];
