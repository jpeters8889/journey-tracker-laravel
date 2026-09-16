<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Controllers;

use Illuminate\Http\Response;
use Jpeters8889\JourneyTrackerLaravel\Http\Requests\HeartbeatRequest;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\Support\RouteResolver;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackingPolicy;

class HeartbeatController
{
    public function __invoke(
        HeartbeatRequest $request,
        TrackingPolicy $trackingPolicy,
        JourneyTracker $journeyTracker,
        RouteResolver $routeResolver,
    ): Response {
        $data = $request->toData($routeResolver);

        if ($trackingPolicy->shouldTrackPath($data->path, $data->route, $data->routePath)) {
            LogPageViewJob::dispatch($data)->onQueue($journeyTracker->queue());
        }

        return response()->noContent();
    }
}
