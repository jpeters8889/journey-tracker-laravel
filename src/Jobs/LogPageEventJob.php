<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedEventData;
use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;
use Jpeters8889\JourneyTrackerLaravel\Concerns\SendsToJourneyTracker;

class LogPageEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SendsToJourneyTracker;

    public function __construct(protected QueuedEventData $data)
    {
        //
    }

    public function handle(): void
    {
        $this->send(IngestType::EVENT, '/api/v1/event', $this->data->toArray());
    }
}
