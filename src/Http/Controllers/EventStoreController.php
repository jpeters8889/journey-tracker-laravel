<?php

namespace Jpeters8889\JourneyTrackerLaravel\Http\Controllers;

use Illuminate\Http\Response;
use Jpeters8889\JourneyTrackerLaravel\Http\Requests\EventStoreRequest;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageEventJob;

class EventStoreController
{
    public function __invoke(EventStoreRequest $request): Response
    {
        LogPageEventJob::dispatch($request->toData());

        return response()->noContent();
    }
}
