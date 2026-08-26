<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Jpeters8889\JourneyTrackerLaravel\Http\Middleware\LogPageViewMiddleware;
use Jpeters8889\JourneyTrackerLaravel\JourneyTracker;

it('logs a page view carrying the full payload', function (): void {
    fakePageViewEndpoint();

    Route::middleware(['web', LogPageViewMiddleware::class])
        ->get('/blog/my-post', fn (): string => 'ok')
        ->name('blog.show');

    $this->withHeader('User-Agent', 'JourneyBot/1.0')->get('/blog/my-post')->assertOk();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data();

        return $data['path'] === 'blog/my-post'
            && $data['route'] === 'blog.show'
            && $data['route_path'] === 'blog/my-post'
            && $data['user_agent'] === 'JourneyBot/1.0'
            && is_string($data['session_id'])
            && $data['session_id'] !== ''
            && is_int($data['timestamp']);
    });
});

it('sends a null route but still sends the route path for an unnamed route', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => 'ok');

    $this->get('/blog');

    Http::assertSent(fn (Request $request): bool => $request->data()['route'] === null
        && $request->data()['route_path'] === 'blog');
});

it('sends the route pattern as the route path, not the path that was visited', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog/{post}', fn (): string => 'ok');

    $this->get('/blog/my-post');

    Http::assertSent(fn (Request $request): bool => $request->data()['path'] === 'blog/my-post'
        && $request->data()['route_path'] === 'blog/{post}');
});

it('stamps the page view with the current time', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => 'ok');

    $before = time();

    $this->get('/blog');

    Http::assertSent(fn (Request $request): bool => $request->data()['timestamp'] >= $before
        && $request->data()['timestamp'] <= time());
});

it('logs nothing for a route excluded by dont-track', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    fakePageViewEndpoint();

    trackedRoute('/cs-adm/dashboard', fn (): string => 'ok');

    $this->get('/cs-adm/dashboard')->assertOk();

    Http::assertNothingSent();
});

it('logs nothing when tracking is disabled', function (): void {
    config(['journey-tracker-laravel.enabled' => false]);

    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => 'ok');

    $this->get('/blog')->assertOk();

    Http::assertNothingSent();
});

it('logs nothing for a request that is not a GET', function (): void {
    fakePageViewEndpoint();

    Route::middleware(['web', LogPageViewMiddleware::class])->post('/blog', fn (): string => 'ok');

    $this->post('/blog')->assertOk();

    Http::assertNothingSent();
});

it('logs nothing for a prefetch', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => 'ok');

    $this->withHeader('Purpose', 'prefetch')->get('/blog')->assertOk();

    Http::assertNothingSent();
});

it('logs nothing for an inertia partial reload', function (): void {
    fakePageViewEndpoint();

    trackedRoute('/blog', fn (): string => 'ok');

    $this->withHeader('X-Inertia-Partial-Component', 'Blog/Index')->get('/blog')->assertOk();

    Http::assertNothingSent();
});

it('lets the response through untouched when the request is not tracked', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => 'the page');

    $response = $this->get('/cs-adm/dashboard');

    expect($response->getContent())->toBe('the page')
        ->and($response->headers->has('X-Journey-Token'))->toBeFalse();
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

it('returns null from the deprecated accessor when the request is not tracked', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    trackedRoute('/cs-adm/dashboard', fn (): string => LogPageViewMiddleware::getToken() ?? 'null');

    expect($this->get('/cs-adm/dashboard')->getContent())->toBe('null');
});
