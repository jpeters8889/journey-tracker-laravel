<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageEventJob;

it('posts the event payload to the api', function (): void {
    fakeEventEndpoint();

    (new LogPageEventJob(queuedEventData()))->handle();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/event')
        && $request->method() === 'POST'
        && $request->data() === [
            'session_id' => 'session-abc',
            'path' => 'blog/my-post',
            'event_type' => 'clicked',
            'event_identifier' => 'BlogDetailCard',
            'data' => ['id' => 7],
            'sensitive' => false,
            'timestamp' => 1787577135,
        ]);
});

it('does not throw when the api is unreachable', function (): void {
    Http::fake(fn () => throw new ConnectionException('offline'));

    (new LogPageEventJob(queuedEventData()))->handle();
})->throwsNoExceptions();

it('does not throw when the api returns a server error', function (): void {
    Http::fake(['*' => Http::response('boom', 500)]);

    (new LogPageEventJob(queuedEventData()))->handle();
})->throwsNoExceptions();
