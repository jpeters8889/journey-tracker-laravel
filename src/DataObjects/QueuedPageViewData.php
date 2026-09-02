<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\DataObjects;

final readonly class QueuedPageViewData
{
    public function __construct(
        public string $visitId,
        public string $path,
        public ?string $route,
        public ?string $routePath,
        public int $timestamp,
        public ?string $userAgent = null,
        public bool $visitKeyWasNew = false,
        public bool $confirmationExpected = false,
    ) {
        //
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'visit_id' => $this->visitId,
            'path' => $this->path,
            'route' => $this->route,
            'route_path' => $this->routePath,
            'timestamp' => $this->timestamp,
            'user_agent' => $this->userAgent,
            'visit_key_was_new' => $this->visitKeyWasNew,
            'confirmation_expected' => $this->confirmationExpected,
        ];
    }
}
