<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

use Illuminate\Support\Str;

/**
 * @internal
 */
class TrackedQuery
{
    /**
     * @param  array<array-key, mixed>  $query
     * @return array<string, string|array<array-key, mixed>>|null
     */
    public function filter(array $query): ?array
    {
        $patterns = $this->patterns();
        $tracked = [];

        foreach ($query as $key => $value) {
            if ( ! Str::is($patterns, (string) $key)) {
                continue;
            }

            $normalised = $this->normalise($value);

            if ($normalised !== null) {
                $tracked[(string) $key] = $normalised;
            }
        }

        ksort($tracked);

        return $tracked === [] ? null : $tracked;
    }

    /** @return array<string, string|array<array-key, mixed>>|null */
    public function filterQueryString(string $queryString): ?array
    {
        parse_str(ltrim($queryString, '?'), $parsed);

        return $this->filter($parsed);
    }

    /** @return list<string> */
    public function scriptPatterns(): array
    {
        return array_map(
            fn (string $pattern): string => '^' . str_replace('\*', '.*', preg_quote($pattern)) . '$',
            $this->patterns(),
        );
    }

    /** @return string|array<array-key, mixed>|null */
    protected function normalise(mixed $value): string|array|null
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        if ( ! is_array($value)) {
            return null;
        }

        $normalised = array_filter(
            array_map($this->normalise(...), $value),
            fn (string|array|null $item): bool => $item !== null,
        );

        if ($normalised === []) {
            return null;
        }

        return array_is_list($value) ? array_values($normalised) : $normalised;
    }

    /** @return list<string> */
    protected function patterns(): array
    {
        return array_values(array_filter(
            config()->array('journey-tracker-laravel.track-query-strings', ['page', 'cursor']),
            is_string(...),
        ));
    }
}
