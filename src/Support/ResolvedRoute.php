<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Support;

final readonly class ResolvedRoute
{
    public function __construct(public ?string $name = null, public ?string $uri = null)
    {
        //
    }
}
