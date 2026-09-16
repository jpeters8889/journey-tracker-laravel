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
            (function(){var t='{$token}',u='{$endpoint}',c='{$confirmEndpoint}',h=false,p=location.pathname;
            function s(){fetch(u,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t,path:location.pathname})}).catch(function(){});}
            function k(){fetch(c,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t})}).catch(function(){});}
            function n(){if(location.pathname===p){return;}p=location.pathname;s();}
            function w(m){try{var o=history[m];if(typeof o!=='function'){return;}history[m]=function(){var r=o.apply(this,arguments);setTimeout(n,0);return r;};}catch(e){}}
            k();
            w('pushState');w('replaceState');
            window.addEventListener('hashchange',function(){h=true;});
            window.addEventListener('popstate',function(){setTimeout(function(){if(h){h=false;return;}p=location.pathname;s();},0);});
            window.addEventListener('pageshow',function(e){if(e.persisted){s();}});}());
            JS;

        return "<script>{$script}</script>";
    }
}
