<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;

it('posts the page view payload to the api', function (): void {
    fakePageViewEndpoint();

    new LogPageViewJob(queuedPageViewData())->handle();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/page-view')
        && $request->method() === 'POST'
        && $request->data() === [
            'session_id' => 'session-abc',
            'path' => 'blog/my-post',
            'route' => 'blog.show',
            'route_path' => 'blog/{post}',
            'timestamp' => 1787577135,
            'user_agent' => 'JourneyBot/1.0',
        ]);
});

it('does not throw when the api is unreachable', function (): void {
    Http::fake(fn () => throw new ConnectionException('offline'));

    new LogPageViewJob(queuedPageViewData())->handle();
})->throwsNoExceptions();

it('does not throw when the api returns a server error', function (): void {
    Http::fake(['*' => Http::response('boom', 500)]);

    new LogPageViewJob(queuedPageViewData())->handle();
})->throwsNoExceptions();
