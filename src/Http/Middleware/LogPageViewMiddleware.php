<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;

class LogPageViewMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->shouldTrack($request)) {
            $data = new QueuedPageViewData(
                $request->session()->getId(),
                $request->path(),
                $request->route()?->getName(),
                now()->getTimestamp(),
            );

            LogPageViewJob::dispatch($data);

            $request->headers->set('X-Journey-Token', Crypt::encrypt([
                'session_id' => $request->session()->getId(),
                'path' => $request->path(),
            ]));
        }

        return $next($request);
    }

    protected function shouldTrack(Request $request): bool
    {
        if (config()->boolean('journey-tracker-laravel.enabled') === false) {
            return false;
        }

        if ($request->method() !== 'GET') {
            return false;
        }

        $dontTrack = config()->array('journey-tracker-laravel.dont-track');

        if (Str::is($dontTrack, $request->path())) {
            return false;
        }

        $routeName = $request->route()?->getName();

        if ($routeName !== null && Str::is($dontTrack, $routeName)) {
            return false;
        }

        $routeUri = $request->route()?->uri();

        return ! ($routeUri !== null && Str::is($dontTrack, $routeUri));
    }
}
