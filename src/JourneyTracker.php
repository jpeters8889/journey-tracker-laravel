<?php

namespace Jpeters8889\JourneyTrackerLaravel;

use Illuminate\Http\Request;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedTagData;
use Jpeters8889\JourneyTrackerLaravel\Jobs\AssignTagJob;

class JourneyTracker
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function tag(string $tag): void
    {
        AssignTagJob::dispatch(new QueuedTagData(
            $this->request->session()->getId(),
            $tag,
        ));
    }
}
