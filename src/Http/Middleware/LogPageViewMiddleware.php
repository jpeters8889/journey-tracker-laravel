<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackedRequest;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackingPolicy;
use Symfony\Component\HttpFoundation\Response;

class LogPageViewMiddleware
{
    public function __construct(
        protected JourneyTracker $journeyTracker,
        protected TrackedRequest $trackedRequest,
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

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->trackingPolicy->shouldTrackRequest($request)) {
            $this->trackedRequest->start();
        }

        $response = $next($request);

        $visitId = $this->trackedRequest->visitId();

        if ($visitId === null) {
            return $response;
        }

        $this->trackedRequest->persistVisit();

        LogPageViewJob::dispatch(new QueuedPageViewData(
            $visitId,
            $request->path(),
            $request->route()?->getName(),
            $request->route()?->uri(),
            time(),
            $request->userAgent(),
            $this->trackedRequest->visitKeyWasNew(),
            $this->trackedRequest->confirmationExpected(),
        ))->onQueue($this->journeyTracker->queue());

        $token = $this->trackedRequest->token();

        if ($token !== null) {
            $response->headers->set('X-Journey-Token', $token);
        }

        return $response;
    }
}
