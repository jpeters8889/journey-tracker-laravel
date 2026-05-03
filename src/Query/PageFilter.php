<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Query;

class PageFilter
{
    private ?string $id = null;

    private ?string $path = null;

    public function id(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $filter = [];

        if ($this->id !== null) {
            $filter['id'] = $this->id;
        }

        if ($this->path !== null) {
            $filter['path'] = $this->path;
        }

        return $filter;
    }
}
