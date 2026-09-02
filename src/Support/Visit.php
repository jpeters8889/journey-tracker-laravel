<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

final readonly class Visit
{
    public function __construct(
        public string $id,
        public int $seen,
        public bool $wasNew,
    ) {
        //
    }

    public static function fromSession(mixed $stored): ?self
    {
        if ( ! is_array($stored)) {
            return null;
        }

        $id = $stored['id'] ?? null;
        $seen = $stored['seen'] ?? null;

        if ( ! is_string($id) || ! is_int($seen)) {
            return null;
        }

        return new self($id, $seen, wasNew: false);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'seen' => $this->seen,
        ];
    }
}
