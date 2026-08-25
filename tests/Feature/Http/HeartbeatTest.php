<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

function heartbeatUrl(): string
{
    return '/' . config('journey-tracker-laravel.heartbeat-endpoint');
}

function heartbeatToken(string $sessionId = 'session-abc', string $path = 'blog'): string
{
    return Crypt::encrypt(['session_id' => $sessionId, 'path' => $path]);
}

it('logs a page view against the token path when no path is sent', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => heartbeatToken(path: 'blog/my-post')])
        ->assertNoContent();

    Http::assertSent(
        fn (Request $request): bool => $request->data()['path'] === 'blog/my-post'
            && $request->data()['session_id'] === 'session-abc'
    );
});

it('prefers a client supplied path over the one baked into the token', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => heartbeatToken(path: 'blog'),
        'path' => 'blog/my-post',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['path'] === 'blog/my-post');
});

it('normalises the leading slash that location.pathname always carries', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => heartbeatToken(),
        'path' => '/blog/my-post',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['path'] === 'blog/my-post');
});

it('keeps the session id from the token when the path is overridden', function (): void {
    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => heartbeatToken(sessionId: 'session-xyz'),
        'path' => '/somewhere-else',
    ])->assertNoContent();

    Http::assertSent(fn (Request $request): bool => $request->data()['session_id'] === 'session-xyz');
});

it('logs nothing when the supplied path is excluded by dont-track', function (): void {
    config(['journey-tracker-laravel.dont-track' => ['cs-adm/*']]);

    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), [
        'token' => heartbeatToken(),
        'path' => '/cs-adm/dashboard',
    ])->assertNoContent();

    Http::assertNothingSent();
});

it('logs nothing when tracking is disabled', function (): void {
    config(['journey-tracker-laravel.enabled' => false]);

    fakePageViewEndpoint();

    $this->postJson(heartbeatUrl(), ['token' => heartbeatToken()])->assertNoContent();

    Http::assertNothingSent();
});

it('requires a token', function (): void {
    $this->postJson(heartbeatUrl(), [])->assertJsonValidationErrorFor('token');
});
