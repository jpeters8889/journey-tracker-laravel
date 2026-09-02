<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;
use Jpeters8889\JourneyTrackerLaravel\Rules\DecryptableToken;
use Jpeters8889\JourneyTrackerLaravel\Support\JourneyToken;

class HeartbeatRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(DecryptableToken $decryptableToken): array
    {
        return [
            'token' => ['required', 'string', $decryptableToken],
            'path' => ['sometimes', 'string'],
        ];
    }

    public function toData(): QueuedPageViewData
    {
        $token = JourneyToken::decrypt($this->string('token')->toString());

        return new QueuedPageViewData(
            visitId: $token->visitId,
            path: $this->trackedPath($token->path),
            route: null,
            routePath: null,
            timestamp: time(),
            userAgent: $this->userAgent(),
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
