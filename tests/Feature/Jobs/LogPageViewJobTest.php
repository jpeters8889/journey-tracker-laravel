<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Jobs\LogPageViewJob;
use Jpeters8889\JourneyTrackerLaravel\Support\VisitKey;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Event;
use Jpeters8889\JourneyTrackerLaravel\Enums\IngestType;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerBlocked;
use Jpeters8889\JourneyTrackerLaravel\Events\JourneyTrackerFailed;

it('posts the page view payload to the api', function (): void {
    fakePageViewEndpoint();

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v1/page-view')
        && $request->method() === 'POST'
        && $request->data() === [
            'visit_id' => 'session-abc',
            'path' => 'blog/my-post',
            'route' => 'blog.show',
            'route_path' => 'blog/{post}',
            'timestamp' => 1787577135,
            'user_agent' => 'JourneyBot/1.0',
            'visit_key_was_new' => false,
            'confirmation_expected' => false,
            'query' => null,
            'sec_fetch_mode' => null,
            'sec_fetch_dest' => null,
            'sec_fetch_user' => null,
        ]);
});

it('does not throw when the api is unreachable', function (): void {
    Http::fake(fn () => throw new ConnectionException('offline'));

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);
})->throwsNoExceptions();

it('does not throw when the api returns a server error', function (): void {
    Http::fake(['*' => Http::response('boom', 500)]);

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);
})->throwsNoExceptions();

it('remembers the visit threshold the platform published', function (): void {
    Http::fake(['*/api/v1/page-view' => Http::response(['visit_threshold_minutes' => 30])]);

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);

    expect(app(VisitKey::class)->thresholdMinutes())->toBe(30);
});

it('keeps the configured threshold when the platform publishes nothing usable', function (mixed $published): void {
    config(['journey-tracker-laravel.visit-threshold-minutes' => 15]);

    Http::fake(['*/api/v1/page-view' => Http::response(['visit_threshold_minutes' => $published])]);

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);

    expect(app(VisitKey::class)->thresholdMinutes())->toBe(15);
})->with([
    'null' => [null],
    'a string' => ['30'],
    'zero' => [0],
]);

it('reports blocked ingest when the platform has paused it', function (): void {
    Event::fake();

    fakePageViewEndpoint(402);

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);

    Event::assertDispatched(
        JourneyTrackerBlocked::class,
        fn (JourneyTrackerBlocked $event): bool => $event->type === IngestType::PAGE_VIEW,
    );

    Event::assertNotDispatched(JourneyTrackerFailed::class);
});

it('reports a failed page view with the status the platform returned', function (): void {
    Event::fake();

    Http::fake(['*' => Http::response('boom', 500)]);

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);

    Event::assertDispatched(
        JourneyTrackerFailed::class,
        fn (JourneyTrackerFailed $event): bool => $event->type === IngestType::PAGE_VIEW
            && $event->status === 500
            && $event->exception instanceof RequestException,
    );

    Event::assertNotDispatched(JourneyTrackerBlocked::class);
});

it('reports a failed page view with no status when the api is unreachable', function (): void {
    Event::fake();

    Http::fake(fn () => throw new ConnectionException('offline'));

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);

    Event::assertDispatched(
        JourneyTrackerFailed::class,
        fn (JourneyTrackerFailed $event): bool => $event->type === IngestType::PAGE_VIEW
            && $event->status === null
            && $event->exception instanceof ConnectionException,
    );
});

it('reports nothing when the page view is accepted', function (): void {
    Event::fake();

    fakePageViewEndpoint();

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);

    Event::assertNothingDispatched();
});

it('keeps the configured threshold when the platform blocks ingest', function (): void {
    config(['journey-tracker-laravel.visit-threshold-minutes' => 15]);

    fakePageViewEndpoint(402);

    app()->call([new LogPageViewJob(queuedPageViewData()), 'handle']);

    expect(app(VisitKey::class)->thresholdMinutes())->toBe(15);
});
