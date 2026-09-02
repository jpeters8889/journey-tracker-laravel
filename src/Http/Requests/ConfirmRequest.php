<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedConfirmationData;
use Jpeters8889\JourneyTrackerLaravel\Rules\DecryptableToken;
use Jpeters8889\JourneyTrackerLaravel\Support\JourneyToken;

class ConfirmRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(DecryptableToken $decryptableToken): array
    {
        return [
            'token' => ['required', 'string', $decryptableToken],
        ];
    }

    public function toData(): QueuedConfirmationData
    {
        $token = JourneyToken::decrypt($this->string('token')->toString());

        return new QueuedConfirmationData(visitId: $token->visitId);
    }
}
