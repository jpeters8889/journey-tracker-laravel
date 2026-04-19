<?php

namespace Jpeters8889\JourneyTrackerLaravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class JourneyTrackerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('journey-tracker-laravel')
            ->hasConfigFile()
            ->hasRoute('api');
    }

    public function boot()
    {
        parent::boot();

        $this->app->bind('journey-tracker', fn() => app(JourneyTracker::class));

        Blade::directive('journeyTracker', fn (): string => '<?php echo app(\'journey-tracker\')->heartbeatScript(); ?>');

        Http::macro('journeyTracker', fn() => Http::baseUrl(config('journey-tracker-laravel.host'))
            ->withToken(config('journey-tracker-laravel.app-token'))
            ->withoutVerifying()
            ->acceptJson()
        );
    }
}
