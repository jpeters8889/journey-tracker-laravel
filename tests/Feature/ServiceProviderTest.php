<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\JourneyTrackerServiceProvider;

it('binds the journey-tracker alias to the tracker', function (): void {
    expect(app('journey-tracker'))->toBeInstanceOf(JourneyTracker::class);
});

it('publishes the config file under the package tag', function (): void {
    $paths = ServiceProvider::pathsToPublish(
        JourneyTrackerServiceProvider::class,
        'journey-tracker-laravel-config',
    );

    expect($paths)->not->toBeEmpty()
        ->and(array_values($paths)[0])->toEndWith('journey-tracker-laravel.php');
});

it('keeps the internal keys out of the published config file', function (): void {
    $paths = ServiceProvider::pathsToPublish(
        JourneyTrackerServiceProvider::class,
        'journey-tracker-laravel-config',
    );

    $published = require array_key_first($paths);

    expect($published)->not->toHaveKey('host')
        ->and($published)->not->toHaveKey('verify-tls')
        ->and($published)->toHaveKeys(['enabled', 'app-token', 'dont-track', 'queue']);
});

it('still resolves the internal keys at runtime', function (): void {
    expect(config('journey-tracker-laravel.host'))->toBe('https://journey-tracker.cloud')
        ->and(config('journey-tracker-laravel.verify-tls'))->toBeTrue();
});

it('falls back to the cloud host when the host key is missing entirely', function (): void {
    $config = config()->array('journey-tracker-laravel');

    unset($config['host']);

    config(['journey-tracker-laravel' => $config]);

    Http::fake();

    Http::journeyTracker()->post('/api/tag', []);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://journey-tracker.cloud/api/tag');
});

it('sends api calls to the configured host', function (): void {
    config(['journey-tracker-laravel.host' => 'https://analytics.example.test']);

    Http::fake();

    Http::journeyTracker()->post('/api/tag', []);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://analytics.example.test/api/tag');
});

it('authenticates api calls with the configured app token', function (): void {
    config(['journey-tracker-laravel.app-token' => 'super-secret-token']);

    expect(Http::journeyTracker()->getOptions()['headers']['Authorization'])->toBe('Bearer super-secret-token');
});

it('sends no authorization header when no app token is configured', function (): void {
    config(['journey-tracker-laravel.app-token' => null]);

    expect(Http::journeyTracker()->getOptions()['headers'])->not->toHaveKey('Authorization');
});

it('identifies itself and its version on every api call', function (): void {
    expect(Http::journeyTracker()->getOptions()['headers']['X-Journey-Tracker-Client'])
        ->toStartWith('laravel/')
        ->not->toBe('laravel/');
});

it('asks for json back', function (): void {
    expect(Http::journeyTracker()->getOptions()['headers']['Accept'])->toBe('application/json');
});

it('verifies tls certificates by default', function (): void {
    expect(Http::journeyTracker()->getOptions()['verify'] ?? true)->toBeTrue();
});

it('skips tls verification only when explicitly configured off', function (): void {
    config(['journey-tracker-laravel.verify-tls' => false]);

    expect(Http::journeyTracker()->getOptions()['verify'])->toBeFalse();
});

it('still verifies tls when the published config predates the verify-tls key', function (): void {
    $published = config()->array('journey-tracker-laravel');

    unset($published['verify-tls']);

    config(['journey-tracker-laravel' => $published]);

    expect(Http::journeyTracker()->getOptions()['verify'] ?? true)->toBeTrue();
});

it('compiles the journeyTracker directive to a heartbeat script call', function (): void {
    expect(Blade::compileString('@journeyTracker'))->toContain('heartbeatScript');
});

it('renders the heartbeat script through the blade directive on a tracked request', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => Blade::render('@journeyTracker'));

    expect($this->get('/blog')->getContent())
        ->toContain('<script>')
        ->toContain('location.pathname');
});

it('renders nothing through the blade directive when the request is not tracked', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => Blade::render('@journeyTracker'));

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('');
});
