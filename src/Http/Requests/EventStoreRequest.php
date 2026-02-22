<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedEventData;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;

class EventStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'event_type' => ['required', Rule::enum(EventType::class)],
            'event_identifier' => ['required', 'string'],
            'data' => ['array'],
            'sensitive' => ['boolean'],
        ];
    }

    public function toData(): QueuedEventData
    {
        /** @var array{session_id: string, path: string} $token */
        $token = Crypt::decrypt($this->string('token')->toString());

        return new QueuedEventData(
            sessionId: $token['session_id'],
            path: $token['path'],
            eventType: $this->enum('event_type', EventType::class),
            eventIdentifier: $this->string('event_identifier')->toString(),
            data: $this->array('data'),
            sensitive: $this->boolean('sensitive'),
            timestamp: time(),
        );
    }
}
