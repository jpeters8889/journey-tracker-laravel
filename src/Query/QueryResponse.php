<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Query;

final readonly class QueryResponse
{
    /**
     * @param array<string, int|list<array{date: string, count: int}>> $data
     */
    public function __construct(private array $data) {}

    /** @return int|list<array{date: string, count: int}> */
    public function get(string $alias): int|array
    {
        return $this->data[$alias];
    }

    /** @return array<string, int|list<array{date: string, count: int}>> */
    public function all(): array
    {
        return $this->data;
    }

    /** @return int|list<array{date: string, count: int}> */
    public function __get(string $alias): int|array
    {
        return $this->get($alias);
    }
}
