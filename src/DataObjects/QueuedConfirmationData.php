<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\DataObjects;

final readonly class QueuedConfirmationData
{
    public function __construct(public string $visitId)
    {
        //
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'visit_id' => $this->visitId,
        ];
    }
}
