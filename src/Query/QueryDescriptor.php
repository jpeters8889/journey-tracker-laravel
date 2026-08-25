<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Query;

use Closure;

class QueryDescriptor
{
    /** @var list<EventFilter> */
    private array $eventFilters = [];

    /** @var list<PageFilter> */
    private array $pageFilters = [];

    public function __construct(public readonly string $alias)
    {
    }

    public function addEventFilter(EventFilter $filter): void
    {
        $this->eventFilters[] = $filter;
    }

    public function addPageFilter(PageFilter $filter): void
    {
        $this->pageFilters[] = $filter;
    }

    public function withEvent(Closure $filter): static
    {
        $eventFilter = new EventFilter();
        $filter($eventFilter);
        $this->eventFilters[] = $eventFilter;

        return $this;
    }

    public function withPage(Closure $filter): static
    {
        $pageFilter = new PageFilter();
        $filter($pageFilter);
        $this->pageFilters[] = $pageFilter;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $has = [];

        if ($this->eventFilters !== []) {
            $has['events'] = array_map(
                fn (EventFilter $filter): array => $filter->toArray(),
                $this->eventFilters,
            );
        }

        if ($this->pageFilters !== []) {
            $has['pages'] = array_map(
                fn (PageFilter $filter): array => $filter->toArray(),
                $this->pageFilters,
            );
        }

        return ['as' => $this->alias, 'has' => $has];
    }
}
