<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Events;

use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;
use Throwable;

final readonly class JourneyTrackerFailed
{
    public function __construct(
        public IngestType $type,
        public ?int $status,
        public Throwable $exception,
    ) {
        //
    }
}
