<?php

declare(strict_types=1);

namespace Jpeters8889\JourneyTrackerLaravel;

use Composer\InstalledVersions;
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

    public function packageRegistered(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/journey-tracker-laravel-internal.php',
            'journey-tracker-laravel',
        );
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->bind('journey-tracker', fn () => app(JourneyTracker::class));

        Blade::directive('journeyTracker', fn (): string => "<?php echo app('journey-tracker')->heartbeatScript(); ?>");

        $client = $this->clientHeader();

        Http::macro('journeyTracker', function () use ($client): PendingRequest {
            $token = config('journey-tracker-laravel.app-token');

            $request = Http::baseUrl(config()->string('journey-tracker-laravel.host', 'https://journey-tracker.cloud'));

            if (is_string($token)) {
                $request = $request->withToken($token);
            }

            if (config()->boolean('journey-tracker-laravel.verify-tls', true) === false) {
                $request = $request->withoutVerifying();
            }

            return $request
                ->withHeaders(['X-Journey-Tracker-Client' => $client])
                ->acceptJson();
        });
    }

    protected function clientHeader(): string
    {
        $version = InstalledVersions::getPrettyVersion('jpeters8889/journey-tracker-laravel');

        return 'laravel/' . ($version ?? 'unknown');
    }
}
