<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Query;

final readonly class QueryResponse
{
    /**
     * @param array<string, int>|list<array<string, mixed>> $data
     */
    public function __construct(private array $data) {}

    /** @return int */
    public function get(string $alias): int
    {
        /** @var int */
        return $this->data[$alias];
    }

    /** @return array<string, int>|list<array<string, mixed>> */
    public function all(): array
    {
        return $this->data;
    }

    /** @return int */
    public function __get(string $alias): int
    {
        return $this->get($alias);
    }
}
