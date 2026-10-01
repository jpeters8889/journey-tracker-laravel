<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;
use Jpeters8889\JourneyTrackerLaravel\Rules\DecryptableToken;
use Jpeters8889\JourneyTrackerLaravel\Support\JourneyToken;
use Jpeters8889\JourneyTrackerLaravel\Support\RouteResolver;
use Jpeters8889\JourneyTrackerLaravel\Support\TrackedQuery;

class HeartbeatRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(DecryptableToken $decryptableToken): array
    {
        return [
            'token' => ['required', 'string', $decryptableToken],
            'path' => ['sometimes', 'string'],
            'query' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function toData(RouteResolver $routeResolver, TrackedQuery $trackedQuery): QueuedPageViewData
    {
        $token = JourneyToken::decrypt($this->string('token')->toString());
        $path = $this->trackedPath($token->path);
        $route = $routeResolver->resolve($path, $this->getHost());

        return new QueuedPageViewData(
            visitId: $token->visitId,
            path: $path,
            route: $route->name,
            routePath: $route->uri,
            timestamp: time(),
            userAgent: $this->userAgent(),
            query: $trackedQuery->filterQueryString($this->string('query')->toString()),
        );
    }

    protected function trackedPath(string $fallback): string
    {
        $supplied = ltrim($this->string('path')->toString(), '/');

        if ($supplied === '') {
            return $fallback;
        }

        return $supplied;
    }
}
