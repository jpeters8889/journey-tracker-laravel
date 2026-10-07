<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedConfirmationData;
use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;
use Jpeters8889\JourneyTrackerLaravel\Concerns\SendsToJourneyTracker;

class ConfirmPageViewJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SendsToJourneyTracker;

    public function __construct(protected QueuedConfirmationData $data)
    {
        //
    }

    public function handle(): void
    {
        $this->send(IngestType::PAGE_VIEW_CONFIRMATION, '/api/v1/page-view/confirm', $this->data->toArray());
    }
}
