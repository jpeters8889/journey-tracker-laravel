<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\DataObjects;

use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;

final readonly class QueuedEventData
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(
        public string $visitId,
        public string $path,
        public EventType $eventType,
        public string $eventIdentifier,
        public array $data,
        public bool $sensitive,
        public int $timestamp,
    ) {
        //
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'session_id' => $this->visitId,
            'path' => $this->path,
            'event_type' => $this->eventType->value,
            'event_identifier' => $this->eventIdentifier,
            'data' => $this->data,
            'sensitive' => $this->sensitive,
            'timestamp' => $this->timestamp,
        ];
    }
}
