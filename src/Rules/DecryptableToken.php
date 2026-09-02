<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Rules;

use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Validation\ValidationRule;
use Jpeters8889\JourneyTrackerLaravel\Support\JourneyToken;
use Throwable;

class DecryptableToken implements ValidationRule
{
    public function __construct(protected Encrypter $encrypter)
    {
        //
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ( ! $this->carriesAJourney($value)) {
            $fail('The :attribute is not a valid journey token.');
        }
    }

    protected function carriesAJourney(mixed $value): bool
    {
        if ( ! is_string($value)) {
            return false;
        }

        try {
            $payload = $this->encrypter->decrypt($value);
        } catch (Throwable) {
            return false;
        }

        return JourneyToken::fromPayload($payload) instanceof JourneyToken;
    }
}
