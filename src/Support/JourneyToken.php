<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Throwable;

final readonly class JourneyToken
{
    public function __construct(
        public string $visitId,
        public string $path,
    ) {
        //
    }

    public static function decrypt(string $value): self
    {
        try {
            $token = self::fromPayload(Crypt::decrypt($value));
        } catch (Throwable) {
            throw new InvalidArgumentException('The journey token could not be decrypted.');
        }

        throw_unless($token instanceof self, new InvalidArgumentException('The journey token does not carry a journey.'));

        return $token;
    }

    public static function fromPayload(mixed $payload): ?self
    {
        if ( ! is_array($payload)) {
            return null;
        }

        $visitId = $payload['visit_id'] ?? $payload['session_id'] ?? null;
        $path = $payload['path'] ?? null;

        if ( ! is_string($visitId) || ! is_string($path)) {
            return null;
        }

        return new self($visitId, $path);
    }
}
