<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Events;

use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;

final readonly class JourneyTrackerBlocked
{
    public function __construct(public IngestType $type)
    {
        //
    }
}
