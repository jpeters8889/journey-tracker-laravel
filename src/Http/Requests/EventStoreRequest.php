<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedEventData;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;
use Jpeters8889\JourneyTrackerLaravel\Rules\DecryptableToken;
use Jpeters8889\JourneyTrackerLaravel\Support\JourneyToken;

class EventStoreRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(DecryptableToken $decryptableToken): array
    {
        return [
            'token' => ['required', 'string', $decryptableToken],
            'event_type' => ['required', Rule::enum(EventType::class)],
            'event_identifier' => ['required', 'string'],
            'data' => ['array'],
            'sensitive' => ['boolean'],
        ];
    }

    public function toData(): QueuedEventData
    {
        $token = JourneyToken::decrypt($this->string('token')->toString());

        return new QueuedEventData(
            visitId: $token->visitId,
            path: $token->path,
            eventType: EventType::from($this->string('event_type')->toString()),
            eventIdentifier: $this->string('event_identifier')->toString(),
            data: $this->array('data'),
            sensitive: $this->boolean('sensitive'),
            timestamp: time(),
        );
    }
}
