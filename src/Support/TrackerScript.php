<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

class TrackerScript
{
    public function __construct(protected TrackedRequest $trackedRequest)
    {
        //
    }

    public function render(): string
    {
        $token = $this->trackedRequest->token();

        if ($token === null) {
            return '';
        }

        $this->trackedRequest->expectConfirmation();

        $endpoint = '/' . config()->string('journey-tracker-laravel.heartbeat-endpoint', 'journey-tracker-api/heartbeat');
        $confirmEndpoint = '/' . config()->string('journey-tracker-laravel.confirm-endpoint', 'journey-tracker-api/confirm');

        $script = <<<JS
            (function(){var t='{$token}',u='{$endpoint}',c='{$confirmEndpoint}',h=false;
            function s(){fetch(u,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t,path:location.pathname})}).catch(function(){});}
            function k(){fetch(c,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t})}).catch(function(){});}
            k();
            window.addEventListener('hashchange',function(){h=true;});
            window.addEventListener('popstate',function(){setTimeout(function(){if(h){h=false;return;}s();},0);});
            window.addEventListener('pageshow',function(e){if(e.persisted){s();}});}());
            JS;

        return "<script>{$script}</script>";
    }
}
