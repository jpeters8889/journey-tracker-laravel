<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackingPolicy
{
    public function shouldTrackRequest(Request $request): bool
    {
        if ($request->method() !== 'GET') {
            return false;
        }

        if ( ! $request->hasSession()) {
            return false;
        }

        if ($this->isPartialRequest($request)) {
            return false;
        }

        if ($this->isPrefetchRequest($request)) {
            return false;
        }

        return $this->shouldTrackPath(
            $request->path(),
            $request->route()?->getName(),
            $request->route()?->uri(),
        );
    }

    public function shouldTrackPath(string $path, ?string $routeName = null, ?string $routeUri = null): bool
    {
        if (config()->boolean('journey-tracker-laravel.enabled', true) === false) {
            return false;
        }

        $dontTrack = $this->dontTrackPatterns();

        if (Str::is($dontTrack, ltrim($path, '/'))) {
            return false;
        }

        if ($routeName !== null && Str::is($dontTrack, $routeName)) {
            return false;
        }

        return $routeUri === null || ! Str::is($dontTrack, $routeUri);
    }

    protected function isPartialRequest(Request $request): bool
    {
        return $request->hasHeader('X-Inertia-Partial-Component');
    }

    protected function isPrefetchRequest(Request $request): bool
    {
        if ($request->header('Purpose') === 'prefetch') {
            return true;
        }

        return str_contains((string) $request->header('Sec-Purpose', ''), 'prefetch');
    }

    /** @return list<string> */
    protected function dontTrackPatterns(): array
    {
        return array_values(array_map(
            fn (string $pattern): string => ltrim($pattern, '/'),
            array_filter(config()->array('journey-tracker-laravel.dont-track', []), is_string(...)),
        ));
    }
}
