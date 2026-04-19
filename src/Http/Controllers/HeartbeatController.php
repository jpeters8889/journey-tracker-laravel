<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Controllers;

use Illuminate\Http\Response;
use Jpeters8889\JourneyTrackerLaravel\Http\Requests\HeartbeatRequest;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;

class HeartbeatController
{
    public function __invoke(HeartbeatRequest $request): Response
    {
        LogPageViewJob::dispatch($request->toData());

        return response()->noContent();
    }
}