<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageEventJob;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerBlocked;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerFailed;

it('posts the event payload to the api', function (): void {
    fakeEventEndpoint();

    new LogPageEventJob(queuedEventData())->handle();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v1/event')
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

    new LogPageEventJob(queuedEventData())->handle();
})->throwsNoExceptions();

it('does not throw when the api returns a server error', function (): void {
    Http::fake(['*' => Http::response('boom', 500)]);

    new LogPageEventJob(queuedEventData())->handle();
})->throwsNoExceptions();

it('reports blocked ingest when the platform has paused it', function (): void {
    Event::fake();

    fakeEventEndpoint(402);

    new LogPageEventJob(queuedEventData())->handle();

    Event::assertDispatched(
        JourneyTrackerBlocked::class,
        fn (JourneyTrackerBlocked $event): bool => $event->type === IngestType::EVENT,
    );

    Event::assertNotDispatched(JourneyTrackerFailed::class);
});

it('reports a failed event with the status the platform returned', function (): void {
    Event::fake();

    Http::fake(['*' => Http::response('boom', 500)]);

    new LogPageEventJob(queuedEventData())->handle();

    Event::assertDispatched(
        JourneyTrackerFailed::class,
        fn (JourneyTrackerFailed $event): bool => $event->type === IngestType::EVENT
            && $event->status === 500
            && $event->exception instanceof RequestException,
    );

    Event::assertNotDispatched(JourneyTrackerBlocked::class);
});

it('reports a failed event with no status when the api is unreachable', function (): void {
    Event::fake();

    Http::fake(fn () => throw new ConnectionException('offline'));

    new LogPageEventJob(queuedEventData())->handle();

    Event::assertDispatched(
        JourneyTrackerFailed::class,
        fn (JourneyTrackerFailed $event): bool => $event->type === IngestType::EVENT
            && $event->status === null
            && $event->exception instanceof ConnectionException,
    );
});

it('reports nothing when the event is accepted', function (): void {
    Event::fake();

    fakeEventEndpoint();

    new LogPageEventJob(queuedEventData())->handle();

    Event::assertNothingDispatched();
});
