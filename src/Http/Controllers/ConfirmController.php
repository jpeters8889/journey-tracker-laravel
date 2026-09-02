<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Controllers;

use Illuminate\Http\Response;
use Jpeters8889\JourneyTrackerLaravel\Http\Requests\ConfirmRequest;
use Jpeters8889\JourneyTrackerLaravel\Jobs\ConfirmPageViewJob;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;

class ConfirmController
{
    public function __invoke(ConfirmRequest $request, JourneyTracker $journeyTracker): Response
    {
        ConfirmPageViewJob::dispatch($request->toData())->onQueue($journeyTracker->queue());

        return response()->noContent();
    }
}
