<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Jobs\AssignTagJob;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerBlocked;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerFailed;

it('posts the tag payload to the api', function (): void {
    fakeTagEndpoint();

    new AssignTagJob(queuedTagData())->handle();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v1/tag')
        && $request->method() === 'POST'
        && $request->data() === ['session_id' => 'session-abc', 'tag' => 'Shop Purchase']);
});

it('does not throw when the api is unreachable', function (): void {
    Http::fake(fn () => throw new ConnectionException('offline'));

    new AssignTagJob(queuedTagData())->handle();
})->throwsNoExceptions();

it('does not throw when the api returns a server error', function (): void {
    Http::fake(['*' => Http::response('boom', 500)]);

    new AssignTagJob(queuedTagData())->handle();
})->throwsNoExceptions();

it('reports blocked ingest when the platform has paused it', function (): void {
    Event::fake();

    fakeTagEndpoint(402);

    new AssignTagJob(queuedTagData())->handle();

    Event::assertDispatched(
        JourneyTrackerBlocked::class,
        fn (JourneyTrackerBlocked $event): bool => $event->type === IngestType::TAG,
    );

    Event::assertNotDispatched(JourneyTrackerFailed::class);
});

it('reports a failed tag with the status the platform returned', function (): void {
    Event::fake();

    Http::fake(['*' => Http::response('boom', 500)]);

    new AssignTagJob(queuedTagData())->handle();

    Event::assertDispatched(
        JourneyTrackerFailed::class,
        fn (JourneyTrackerFailed $event): bool => $event->type === IngestType::TAG
            && $event->status === 500
            && $event->exception instanceof RequestException,
    );

    Event::assertNotDispatched(JourneyTrackerBlocked::class);
});

it('reports a failed tag with no status when the api is unreachable', function (): void {
    Event::fake();

    Http::fake(fn () => throw new ConnectionException('offline'));

    new AssignTagJob(queuedTagData())->handle();

    Event::assertDispatched(
        JourneyTrackerFailed::class,
        fn (JourneyTrackerFailed $event): bool => $event->type === IngestType::TAG
            && $event->status === null
            && $event->exception instanceof ConnectionException,
    );
});

it('reports nothing when the tag is accepted', function (): void {
    Event::fake();

    fakeTagEndpoint();

    new AssignTagJob(queuedTagData())->handle();

    Event::assertNothingDispatched();
});
