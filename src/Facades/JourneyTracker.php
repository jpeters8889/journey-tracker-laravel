<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Jpeters8889\JourneyTrackerLaravel\JourneyTracker
 * @method static void tag(string $tag)
 * @method static string heartbeatScript()
 * @method static \Jpeters8889\JourneyTrackerLaravel\Query\QueryBuilder query()
 * @method static string|null token()
 * @method static string|null visitId()
 * @method static bool isTracking()
 */
class JourneyTracker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'journey-tracker';
    }
}
