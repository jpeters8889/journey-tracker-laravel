<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitKey
{
    public function __construct(protected Request $request, protected Repository $cache)
    {
        //
    }

    public function resolve(): Visit
    {
        $stored = Visit::fromSession($this->request->session()->get($this->sessionKey()));
        $now = now()->getTimestamp();

        if ( ! $stored instanceof Visit || ($now - $stored->seen) > $this->thresholdSeconds()) {
            return $this->persist(new Visit(Str::uuid()->toString(), $now, wasNew: true));
        }

        return $this->persist(new Visit($stored->id, $now, wasNew: false));
    }

    public function persist(Visit $visit): Visit
    {
        $this->request->session()->put($this->sessionKey(), $visit->toArray());

        return $visit;
    }

    /**
     * @internal
     */
    public function rememberThreshold(int $minutes): void
    {
        if ($minutes < 1) {
            return;
        }

        if ($this->cache->get($this->cacheKey()) === $minutes) {
            return;
        }

        $this->cache->forever($this->cacheKey(), $minutes);
    }

    public function thresholdMinutes(): int
    {
        $cached = $this->cache->get($this->cacheKey());

        if (is_int($cached) && $cached > 0) {
            return $cached;
        }

        return max(1, config()->integer('journey-tracker-laravel.visit-threshold-minutes', 15));
    }

    protected function thresholdSeconds(): int
    {
        return $this->thresholdMinutes() * 60;
    }

    protected function sessionKey(): string
    {
        return 'journey-tracker.visit';
    }

    protected function cacheKey(): string
    {
        return 'journey-tracker-laravel.visit-threshold';
    }
}
