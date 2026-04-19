<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedPageViewData;

class HeartbeatRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
        ];
    }

    public function toData(): QueuedPageViewData
    {
        /** @var array{session_id: string, path: string} $token */
        $token = Crypt::decrypt($this->string('token')->toString());

        return new QueuedPageViewData(
            sessionId: $token['session_id'],
            path: $token['path'],
            route: null,
            timestamp: time(),
            userAgent: $this->userAgent(),
        );
    }
}