<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Query;

use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;

class EventFilter
{
    private ?string $type = null;

    private ?string $identifier = null;

    /** @var array<string, mixed>|null */
    private ?array $parameters = null;

    public function type(EventType|string $type): static
    {
        $this->type = $type instanceof EventType ? $type->value : $type;

        return $this;
    }

    public function identifier(string $identifier): static
    {
        $this->identifier = $identifier;

        return $this;
    }

    /** @param array<string, mixed> $parameters */
    public function withParameters(array $parameters): static
    {
        $this->parameters = $parameters;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $filter = [];

        if ($this->type !== null) {
            $filter['type'] = $this->type;
        }

        if ($this->identifier !== null) {
            $filter['identifier'] = $this->identifier;
        }

        if ($this->parameters !== null) {
            $filter['parameters'] = $this->parameters;
        }

        return $filter;
    }
}
