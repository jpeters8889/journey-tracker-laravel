<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Controllers;

use Illuminate\Http\Response;
use Jpeters8889\JourneyTrackerLaravel\Http\Requests\EventStoreRequest;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageEventJob;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;

class EventStoreController
{
    public function __invoke(EventStoreRequest $request, JourneyTracker $journeyTracker): Response
    {
        LogPageEventJob::dispatch($request->toData())
            ->onQueue($journeyTracker->queue());

        return response()->noContent();
    }
}
