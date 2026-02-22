<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\DataObjects;

final readonly class QueuedPageViewData
{
    public function __construct(
        public string $sessionId,
        public string $path,
        public ?string $route,
        public int $timestamp,
    ) {
        //
    }

    public function toArray(): array
    {
        return [
            'session_id' => $this->sessionId,
            'path' => $this->path,
            'route' => $this->route,
            'timestamp' => $this->timestamp,
        ];
    }
}
