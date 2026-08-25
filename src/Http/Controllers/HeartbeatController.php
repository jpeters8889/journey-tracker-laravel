<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Controllers;

use Illuminate\Http\Response;
use Jpeters8889\JourneyTrackerLaravel\Http\Requests\HeartbeatRequest;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackingPolicy;

class HeartbeatController
{
    public function __invoke(HeartbeatRequest $request, TrackingPolicy $trackingPolicy): Response
    {
        $data = $request->toData();

        if ($trackingPolicy->shouldTrackPath($data->path)) {
            LogPageViewJob::dispatch($data)->onQueue(config('journey-tracker-laravel.queue'));
        }

        return response()->noContent();
    }
}
