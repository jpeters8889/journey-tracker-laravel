<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

use Illuminate\Support\Js;

class TrackerScript
{
    public function __construct(protected TrackedRequest $trackedRequest, protected TrackedQuery $trackedQuery)
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
        $queryPatterns = Js::from($this->trackedQuery->scriptPatterns());

        $script = <<<JS
            (function(){var t='{$token}',u='{$endpoint}',c='{$confirmEndpoint}',h=false,r={$queryPatterns},p=l();
            function q(){var o=[];new URLSearchParams(location.search).forEach(function(v,k){var b=k.split('[')[0];for(var i=0;i<r.length;i++){if(new RegExp(r[i]).test(b)){o.push(k+'='+v);break;}}});return o.sort().join('&');}
            function l(){return location.pathname+'?'+q();}
            function s(){fetch(u,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t,path:location.pathname,query:location.search})}).catch(function(){});}
            function k(){fetch(c,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t})}).catch(function(){});}
            function n(){if(l()===p){return;}p=l();s();}
            function w(m){try{var o=history[m];if(typeof o!=='function'){return;}history[m]=function(){var r=o.apply(this,arguments);setTimeout(n,0);return r;};}catch(e){}}
            k();
            w('pushState');w('replaceState');
            window.addEventListener('hashchange',function(){h=true;});
            window.addEventListener('popstate',function(){setTimeout(function(){if(h){h=false;return;}p=l();s();},0);});
            window.addEventListener('pageshow',function(e){if(e.persisted){s();}});}());
            JS;

        return "<script>{$script}</script>";
    }
}
