<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Jobs;

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedEventData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Throwable;

class LogPageEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(protected QueuedEventData $data)
    {
        //
    }

    public function handle(): void
    {
        try {
            Http::journeyTracker()->post('/api/event', $this->data->toArray());
        } catch (Throwable $e) {
//            dd($e);
        }
    }
}
