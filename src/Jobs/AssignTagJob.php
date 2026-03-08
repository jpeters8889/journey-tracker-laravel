<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedTagData;
use Throwable;

class AssignTagJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(protected QueuedTagData $data)
    {
        //
    }

    public function handle(): void
    {
        try {
            Http::journeyTracker()->post('/api/tag', $this->data->toArray());
        } catch (Throwable $e) {
//            dd($e);
        }
    }
}
