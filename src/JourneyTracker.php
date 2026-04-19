<?php

namespace Jpeters8889\JourneyTrackerLaravel;

use Illuminate\Http\Request;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedTagData;
use Jpeters8889\JourneyTrackerLaravel\Http\Middleware\LogPageViewMiddleware;
use Jpeters8889\JourneyTrackerLaravel\Jobs\AssignTagJob;

class JourneyTracker
{
    public function __construct(protected Request $request)
    {
        //
    }

    public function tag(string $tag): void
    {
        AssignTagJob::dispatch(new QueuedTagData(
            $this->request->session()->getId(),
            $tag,
        ));
    }

    public function heartbeatScript(): string
    {
        $token = LogPageViewMiddleware::getToken();

        if ($token === null) {
            return '';
        }

        $endpoint = '/' . config('journey-tracker-laravel.heartbeat-endpoint');

        return "<script>(function(){window.addEventListener('pageshow',function(e){if(!e.persisted)return;fetch('{$endpoint}',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:'{$token}'})}).catch(function(){});});}());</script>";
    }
}
