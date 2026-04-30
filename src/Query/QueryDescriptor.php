<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Query;

class QueryDescriptor
{
    /** @var list<EventFilter> */
    private array $eventFilters = [];

    public function __construct(public readonly string $alias) {}

    public function addEventFilter(EventFilter $filter): void
    {
        $this->eventFilters[] = $filter;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $has = [];

        if ($this->eventFilters !== []) {
            $has['events'] = array_map(
                fn(EventFilter $filter): array => $filter->toArray(),
                $this->eventFilters,
            );
        }

        return ['as' => $this->alias, 'has' => $has];
    }
}
