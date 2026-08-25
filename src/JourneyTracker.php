<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedTagData;
use Jpeters8889\JourneyTrackerLaravel\Jobs\AssignTagJob;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryBuilder;

class JourneyTracker
{
    public function __construct(protected Request $request, protected Encrypter $encrypter)
    {
        //
    }

    /**
     * @internal
     */
    public function startTracking(): void
    {
        $this->request->attributes->set($this->payloadKey(), [
            'session_id' => $this->request->session()->getId(),
            'path' => $this->request->path(),
        ]);
    }

    public function isTracking(): bool
    {
        return $this->payload() !== null;
    }

    public function sessionId(): ?string
    {
        return $this->payload()['session_id'] ?? null;
    }

    public function token(): ?string
    {
        $payload = $this->payload();

        if ($payload === null) {
            return null;
        }

        /** @var string|null $token */
        $token = $this->request->attributes->get($this->tokenKey());

        if ($token === null) {
            $token = $this->encrypter->encrypt($payload);

            $this->request->attributes->set($this->tokenKey(), $token);
        }

        return $token;
    }

    public function tag(string $tag): void
    {
        AssignTagJob::dispatch(new QueuedTagData(
            $this->sessionId() ?? $this->request->session()->getId(),
            $tag,
        ))->onQueue(config('journey-tracker-laravel.queue'));
    }

    public function query(): QueryBuilder
    {
        return new QueryBuilder();
    }

    public function heartbeatScript(): string
    {
        $token = $this->token();

        if ($token === null) {
            return '';
        }

        $endpoint = '/' . config('journey-tracker-laravel.heartbeat-endpoint');

        $script = <<<JS
            (function(){var t='{$token}',u='{$endpoint}',h=false;
            function s(){fetch(u,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({token:t,path:location.pathname})}).catch(function(){});}
            window.addEventListener('hashchange',function(){h=true;});
            window.addEventListener('popstate',function(){setTimeout(function(){if(h){h=false;return;}s();},0);});
            window.addEventListener('pageshow',function(e){if(e.persisted){s();}});}());
            JS;

        return "<script>{$script}</script>";
    }

    /** @return array{session_id: string, path: string}|null */
    private function payload(): ?array
    {
        /** @var array{session_id: string, path: string}|null $payload */
        $payload = $this->request->attributes->get($this->payloadKey());

        return $payload;
    }

    private function payloadKey(): string
    {
        return 'journey-tracker.payload';
    }

    private function tokenKey(): string
    {
        return 'journey-tracker.token';
    }
}
