<?php

declare(strict_types=1);

namespace Tests;

use Jpeters8889\JourneyTrackerLaravel\JourneyTrackerServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            JourneyTrackerServiceProvider::class,
        ];
    }
}
