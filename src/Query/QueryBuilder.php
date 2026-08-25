<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Query;

use Closure;
use Illuminate\Support\Facades\Http;

class QueryBuilder
{
    private ?string $from = null;

    private ?string $to = null;

    private bool $daily = false;

    /** @var list<QueryDescriptor> */
    private array $descriptors = [];

    public function from(string|\DateTimeInterface $date): static
    {
        $this->from = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date;

        return $this;
    }

    public function to(string|\DateTimeInterface $date): static
    {
        $this->to = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date;

        return $this;
    }

    public function between(string|\DateTimeInterface $from, string|\DateTimeInterface $to): static
    {
        return $this->from($from)->to($to);
    }

    public function today(string|\DateTimeInterface|null $today = null): static
    {
        $today ??= 'today';

        return $this->from($today)->to($today);
    }

    public function daily(): static
    {
        $this->daily = true;

        return $this;
    }

    public function count(string $alias, ?Closure $configure = null): static
    {
        $descriptor = new QueryDescriptor($alias);

        if ($configure !== null) {
            $configure($descriptor);
        }

        $this->descriptors[] = $descriptor;

        return $this;
    }

    public function withEvent(Closure $filter): static
    {
        $eventFilter = new EventFilter();
        $filter($eventFilter);

        $this->descriptors[array_key_last($this->descriptors)]->addEventFilter($eventFilter);

        return $this;
    }

    public function withPage(Closure $filter): static
    {
        $pageFilter = new PageFilter();
        $filter($pageFilter);

        $this->descriptors[array_key_last($this->descriptors)]->addPageFilter($pageFilter);

        return $this;
    }

    public function get(): QueryResponse
    {
        /** @var array<string, int|list<array{date: string, count: int}>> $data */
        $data = Http::journeyTracker()->post('/api/query', $this->buildPayload())->json('data');

        return new QueryResponse($data);
    }

    /** @param array<string, mixed> $payload */
    public function raw(array $payload): QueryResponse
    {
        /** @var array<string, int|list<array{date: string, count: int}>> $data */
        $data = Http::journeyTracker()->post('/api/query', $payload)->json('data');

        return new QueryResponse($data);
    }

    /** @return array<string, mixed> */
    private function buildPayload(): array
    {
        $payload = ['type' => 'count'];

        if ($this->daily) {
            $payload['interval'] = 'daily';
        }

        if ($this->from !== null) {
            $payload['from'] = $this->from;
        }

        if ($this->to !== null) {
            $payload['to'] = $this->to;
        }

        $payload['data'] = array_map(
            fn(QueryDescriptor $descriptor): array => $descriptor->toArray(),
            $this->descriptors,
        );

        return $payload;
    }
}
