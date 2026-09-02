<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel;

use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedTagData;
use Jpeters8889\JourneyTrackerLaravel\Jobs\AssignTagJob;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryBuilder;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackedRequest;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackerScript;

class JourneyTracker
{
    public function __construct(
        protected TrackedRequest $trackedRequest,
        protected TrackerScript $trackerScript,
    ) {
        //
    }

    public function isTracking(): bool
    {
        return $this->trackedRequest->isTracking();
    }

    public function visitId(): ?string
    {
        return $this->trackedRequest->visitId();
    }

    public function token(): ?string
    {
        return $this->trackedRequest->token();
    }

    public function tag(string $tag): void
    {
        $visitId = $this->visitId();

        if ($visitId === null) {
            return;
        }

        AssignTagJob::dispatch(new QueuedTagData($visitId, $tag))->onQueue($this->queue());
    }

    public function query(): QueryBuilder
    {
        return new QueryBuilder();
    }

    public function heartbeatScript(): string
    {
        return $this->trackerScript->render();
    }

    /**
     * @internal
     */
    public function queue(): ?string
    {
        $queue = config('journey-tracker-laravel.queue');

        return is_string($queue) ? $queue : null;
    }
}
