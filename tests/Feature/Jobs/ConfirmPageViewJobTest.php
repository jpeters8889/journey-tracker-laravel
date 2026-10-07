<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\DataObjects\QueuedConfirmationData;
use Jpeters8889\JourneyTrackerLaravel\Jobs\ConfirmPageViewJob;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerBlocked;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerFailed;

it('posts the confirmation to the api', function (): void {
    fakeConfirmEndpoint();

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v1/page-view/confirm')
        && $request->method() === 'POST'
        && $request->data() === ['visit_id' => 'visit-abc']);
});

it('does not throw when the api is unreachable', function (): void {
    Http::fake(fn () => throw new ConnectionException('offline'));

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();
})->throwsNoExceptions();

it('does not throw when the api returns a server error', function (): void {
    Http::fake(['*' => Http::response('boom', 500)]);

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();
})->throwsNoExceptions();

it('reports blocked ingest when the platform has paused it', function (): void {
    Event::fake();

    fakeConfirmEndpoint(402);

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();

    Event::assertDispatched(
        JourneyTrackerBlocked::class,
        fn (JourneyTrackerBlocked $event): bool => $event->type === IngestType::PAGE_VIEW_CONFIRMATION,
    );

    Event::assertNotDispatched(JourneyTrackerFailed::class);
});

it('reports a failed confirmation with the status the platform returned', function (): void {
    Event::fake();

    Http::fake(['*' => Http::response('boom', 500)]);

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();

    Event::assertDispatched(
        JourneyTrackerFailed::class,
        fn (JourneyTrackerFailed $event): bool => $event->type === IngestType::PAGE_VIEW_CONFIRMATION
            && $event->status === 500
            && $event->exception instanceof RequestException,
    );

    Event::assertNotDispatched(JourneyTrackerBlocked::class);
});

it('reports a failed confirmation with no status when the api is unreachable', function (): void {
    Event::fake();

    Http::fake(fn () => throw new ConnectionException('offline'));

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();

    Event::assertDispatched(
        JourneyTrackerFailed::class,
        fn (JourneyTrackerFailed $event): bool => $event->type === IngestType::PAGE_VIEW_CONFIRMATION
            && $event->status === null
            && $event->exception instanceof ConnectionException,
    );
});

it('reports nothing when the confirmation is accepted', function (): void {
    Event::fake();

    fakeConfirmEndpoint();

    new ConfirmPageViewJob(new QueuedConfirmationData('visit-abc'))->handle();

    Event::assertNothingDispatched();
});
