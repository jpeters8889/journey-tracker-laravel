<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Jobs;

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Support\VisitKey;
use Throwable;

class LogPageViewJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(protected QueuedPageViewData $data)
    {
        //
    }

    public function handle(VisitKey $visitKey): void
    {
        try {
            $response = Http::journeyTracker()->post('/api/v1/page-view', $this->data->toArray());

            $threshold = $response->json('visit_threshold_minutes');

            if (is_int($threshold)) {
                $visitKey->rememberThreshold($threshold);
            }
        } catch (Throwable) {
            //
        }
    }
}
