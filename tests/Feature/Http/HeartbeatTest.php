<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

it('logs a page view against the token path when no path is sent', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => journeyToken(path: 'blog/my-post')])
        ->assertNoContent();

    Http::assertSent(
        fn (Request $request): bool => $request->data()['path'] === 'blog/my-post'
            && $request->data()['visit_id'] === 'session-abc'
    );
});

it('prefers a client supplied path over the one baked into the token', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(path: 'blog'),
        'path' => 'blog/my-post',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['path'] === 'blog/my-post');
});

it('sends only the allowed query string parameters', function (): void {
    config(['journey-tracker-laravel.track-query-strings' => ['page', 'meals']]);

    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(),
        'path' => '/recipes',
        'query' => '?page=2&meals=lunch&fbclid=abc',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['query'] === ['meals' => 'lunch', 'page' => '2']);
});

it('sends a null query when no query string is sent', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => journeyToken()])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['query'] === null);
});

it('sends a null query when nothing in the query string is allowed', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(),
        'query' => '?fbclid=abc&utm_source=newsletter',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['query'] === null);
});

it('rejects a query that is not a string', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(),
        'query' => ['page' => '2'],
    ])->assertUnprocessable();

    Http::assertNothingSent();
});

it('normalises the leading slash that location.pathname always carries', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(),
        'path' => '/blog/my-post',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['path'] === 'blog/my-post');
});

it('keeps the session id from the token when the path is overridden', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(visitId: 'session-xyz'),
        'path' => '/somewhere-else',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['visit_id'] === 'session-xyz');
});

it('logs nothing when the supplied path is excluded by dont-track', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(),
        'path' => '/cs-adm/dashboard',
    ])->assertNoContent();

    Http::assertNothingSent();
});

it('logs nothing when tracking is disabled', function (): void {
    config(['journey-tracker-laravel.enabled' => false]);

    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => journeyToken()])->assertNoContent();

    Http::assertNothingSent();
});

it('requires a token', function (): void {
    $this->postJson(heartbeatUrl(), [])->assertJsonValidationErrorFor('token');
});

it('falls back to the token path when the supplied path is just a slash', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(path: 'blog/my-post'),
        'path' => '/',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['path'] === 'blog/my-post');
});

it('resolves the route the client supplied path belongs to', function (): void {
    Route::get('blog/{post}', fn (): string => 'ok')->name('blog.show');

    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => journeyToken(path: 'blog/my-post')])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['route'] === 'blog.show'
        && $request->data()['route_path'] === 'blog/{post}');
});

it('records a heartbeat with a null route and route path when nothing matches the path', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => journeyToken(path: 'nothing/here')])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['route'] === null
        && $request->data()['route_path'] === null);
});

it('passes the user agent of the heartbeat request through', function (): void {
    fakePageViewEndpoint();

    $this->withHeader('User-Agent', 'JourneyBot/1.0')
        ->postJson(heartbeatUrl(), ['token' => journeyToken()])
        ->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['user_agent'] === 'JourneyBot/1.0');
});

it('stamps the heartbeat with the current server time', function (): void {
    fakePageViewEndpoint();

    $before = time();

    $this->postJson(heartbeatUrl(), ['token' => journeyToken()])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['timestamp'] >= $before
        && $request->data()['timestamp'] <= time());
});

it('is blocked by a dont-track route name now a heartbeat resolves its route', function (): void {
    Route::get('blog/{post}', fn (): string => 'ok')->name('blog.show');

    config(['journey-tracker-laravel.dont-track' => ['blog.show']]);

    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => journeyToken(path: 'blog/my-post')])->assertNoContent();

    Http::assertNothingSent();
});

it('rejects a tampered token with a validation error rather than a server error', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => 'not-a-real-token'])
        ->assertJsonValidationErrorFor('token');

    Http::assertNothingSent();
});

it('rejects a path that is not a string', function (): void {
    $this->postJson(heartbeatUrl(), [
        'token' => journeyToken(),
        'path' => ['blog'],
    ])->assertJsonValidationErrorFor('path');
});

it('still logs a page view from a token minted before the visit id rename', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => legacyJourneyToken(visitId: 'session-abc', path: 'blog/my-post')])
        ->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['visit_id'] === 'session-abc'
        && $request->data()['path'] === 'blog/my-post');
});
