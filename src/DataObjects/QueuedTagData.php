<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\DataObjects;

use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;

final readonly class QueuedTagData
{
    public function __construct(
        public string $sessionId,
        public string $tag,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'tag' => $this->tag,
        ];
    }
}
