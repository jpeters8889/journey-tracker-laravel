<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;
use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;
use Jpeters8889\JourneyTrackerLaravel\Concerns\SendsToJourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\Support\VisitKey;

class LogPageViewJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SendsToJourneyTracker;

    public function __construct(protected QueuedPageViewData $data)
    {
        //
    }

    public function handle(VisitKey $visitKey): void
    {
        $threshold = $this->send(IngestType::PAGE_VIEW, '/api/v1/page-view', $this->data->toArray())
            ?->json('visit_threshold_minutes');

        if (is_int($threshold)) {
            $visitKey->rememberThreshold($threshold);
        }
    }
}
