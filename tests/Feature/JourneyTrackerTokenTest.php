<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;
use Jpeters8889\JourneyTrackerLaravel\Http\Middleware\LogPageViewMiddleware;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;

function trackedRoute(string $uri, Closure $handler): void
{
    Route::middleware(['web', LogPageViewMiddleware::class])->get($uri, $handler);
}

function untrackedRoute(string $uri, Closure $handler): void
{
    Route::middleware(['web'])->get($uri, $handler);
}

it('exposes a token during a tracked request', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->token() ?? 'null');

    expect($this->get('/blog')->getContent())->not->toBe('null');
});

it('returns the same token from two separately resolved instances', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->token() === app(JourneyTracker::class)->token()
        ? 'same'
        : 'different');

    expect($this->get('/blog')->getContent())->toBe('same');
});

it('sends the identical token in the response header and the page body', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->token() ?? 'null');

    $response = $this->get('/blog');

    expect($response->headers->get('X-Journey-Token'))->toBe($response->getContent());
});

it('sets a header that decrypts to the request session and path even when nobody reads the token', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog/my-post', fn (): string => 'ok');

    $header = $this->get('/blog/my-post')->headers->get('X-Journey-Token');

    expect(Crypt::decrypt((string) $header))->toHaveKey('path', 'blog/my-post');
});

it('returns the same value from the deprecated middleware accessor', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => LogPageViewMiddleware::getToken() === app(JourneyTracker::class)->token()
        ? 'same'
        : 'different');

    expect($this->get('/blog')->getContent())->toBe('same');
});

it('has no token on a route excluded by dont-track', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => app(JourneyTracker::class)->token() ?? 'null');

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('null');
});

it('reports isTracking as false on an excluded route', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => app(JourneyTracker::class)->isTracking() ? 'yes' : 'no');

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('no');
});

it('does not leak a token into a later request that never ran the middleware', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->token() ?? 'null');
    untrackedRoute('/standalone', fn (): string => app(JourneyTracker::class)->token() ?? 'null');

    expect($this->get('/blog')->getContent())->not->toBe('null')
        ->and($this->get('/standalone')->getContent())->toBe('null');
});

it('renders no heartbeat script when the request is not tracked', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('');
});

it('renders a heartbeat script listening for both popstate and pageshow', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => app(JourneyTracker::class)->heartbeatScript());

    expect($this->get('/blog')->getContent())
        ->toContain('popstate')
        ->toContain('pageshow')
        ->toContain('location.pathname');
});
