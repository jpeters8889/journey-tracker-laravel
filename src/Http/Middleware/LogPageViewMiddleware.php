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
    private static ?\Closure $tokenCallback = null;

    public static function getToken(): ?string
    {
        return self::$tokenCallback ? (self::$tokenCallback)() : null;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        self::$tokenCallback = $this->shouldTrack($request)
            ? fn (): string => Crypt::encrypt(['session_id' => $request->session()->getId(), 'path' => $request->path()])
            : null;

        $response = $next($request);

        if (self::$tokenCallback !== null) {
            $sessionId = $request->session()->getId();

            LogPageViewJob::dispatch(new QueuedPageViewData(
                $sessionId,
                $request->path(),
                $request->route()?->getName(),
                now()->getTimestamp(),
                $request->userAgent(),
            ));

            $response->headers->set('X-Journey-Token', Crypt::encrypt([
                'session_id' => $sessionId,
                'path' => $request->path(),
            ]));
        }

        return $response;
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
