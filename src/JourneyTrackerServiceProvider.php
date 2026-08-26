<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel;

use Illuminate\Http\Client\PendingRequest;
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

    public function boot(): void
    {
        parent::boot();

        $this->app->bind('journey-tracker', fn () => app(JourneyTracker::class));

        Blade::directive('journeyTracker', fn (): string => "<?php echo app('journey-tracker')->heartbeatScript(); ?>");

        Http::macro(
            'journeyTracker',
            fn (): PendingRequest => Http::baseUrl(config('journey-tracker-laravel.host'))
                ->withToken(config('journey-tracker-laravel.app-token'))
                ->when(
                    config()->boolean('journey-tracker-laravel.verify-tls', true) === false,
                    fn (PendingRequest $request): PendingRequest => $request->withoutVerifying(),
                )
                ->acceptJson()
        );
    }
}
