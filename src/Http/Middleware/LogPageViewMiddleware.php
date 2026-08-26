<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackingPolicy;

class LogPageViewMiddleware
{
    public function __construct(
        protected JourneyTracker $journeyTracker,
        protected TrackingPolicy $trackingPolicy,
    ) {
        //
    }

    /**
     * @deprecated Use JourneyTracker::token(), or the JourneyTracker facade.
     */
    public static function getToken(): ?string
    {
        return app(JourneyTracker::class)->token();
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->trackingPolicy->shouldTrackRequest($request)) {
            $this->journeyTracker->startTracking();
        }

        $response = $next($request);

        $sessionId = $this->journeyTracker->sessionId();

        if ($sessionId === null) {
            return $response;
        }

        LogPageViewJob::dispatch(new QueuedPageViewData(
            $sessionId,
            $request->path(),
            $request->route()?->getName(),
            time(),
            $request->userAgent(),
        ))->onQueue(config('journey-tracker-laravel.queue'));

        $token = $this->journeyTracker->token();

        if ($token !== null) {
            $response->headers->set('X-Journey-Token', $token);
        }

        return $response;
    }
}
