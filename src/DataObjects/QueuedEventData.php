<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\DataObjects;

use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;

final readonly class QueuedEventData
{
    public function __construct(
        public string $sessionId,
        public string $path,
        public EventType $eventType,
        public string $eventIdentifier,
        public array $data,
        public bool $sensitive,
        public int $timestamp,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'path' => $this->path,
            'event_type' => $this->eventType->value,
            'event_identifier' => $this->eventIdentifier,
            'data' => $this->data,
            'sensitive' => $this->sensitive,
            'timestamp' => $this->timestamp,
        ];
    }
}
