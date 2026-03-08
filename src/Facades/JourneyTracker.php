<?php

namespace Jpeters8889\JourneyTrackerLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jpeters8889\JourneyTrackerLaravel\JourneyTracker
 *
 * @method static void tag(string $tag)
 */
class JourneyTracker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'journey-tracker';
    }
}
