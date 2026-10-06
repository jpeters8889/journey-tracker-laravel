<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\DataObjects;

final readonly class QueuedPageViewData
{
    /** @param  array<string, string|array<array-key, mixed>>|null  $query */
    public function __construct(
        public string $visitId,
        public string $path,
        public ?string $route,
        public ?string $routePath,
        public int $timestamp,
        public ?string $userAgent = null,
        public bool $visitKeyWasNew = false,
        public bool $confirmationExpected = false,
        public ?array $query = null,
        public ?string $secFetchMode = null,
        public ?string $secFetchDest = null,
        public ?string $secFetchUser = null,
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
            'query' => $this->query,
            'sec_fetch_mode' => $this->secFetchMode,
            'sec_fetch_dest' => $this->secFetchDest,
            'sec_fetch_user' => $this->secFetchUser,
        ];
    }
}
