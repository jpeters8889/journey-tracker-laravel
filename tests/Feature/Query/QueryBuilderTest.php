<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;
use Jpeters8889\JourneyTrackerLaravel\Facades\JourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\Query\EventFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\PageFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryBuilder;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryDescriptor;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryResponse;

it('sends type count in every payload', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->count('metric')->get();

    Http::assertSent(fn (Request $request): bool => $request->data()['type'] === 'count');
});

it('sends the today keyword for both from and to when today() is called', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->today()->count('metric')->get();

    Http::assertSent(
        fn (Request $request): bool =>
        $request->data()['from'] === 'today' &&
        $request->data()['to'] === 'today'
    );
});

it("does not resolve today against this application's timezone", function (string $timezone): void {
    config(['app.timezone' => $timezone]);
    date_default_timezone_set($timezone);

    fakeQueryResponse();

    new QueryBuilder()->today()->count('metric')->get();

    Http::assertSent(fn (Request $request): bool => $request->data()['from'] === 'today');
})->with(['UTC', 'Europe/London', 'America/Los_Angeles', 'Pacific/Chatham']);

it('still sends an explicit date when one is passed to today()', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->today('2026-08-24')->count('metric')->get();

    Http::assertSent(
        fn (Request $request): bool =>
        $request->data()['from'] === '2026-08-24' &&
        $request->data()['to'] === '2026-08-24'
    );
});

it('sends from and to when set via between()', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->between('2024-01-01', '2024-01-31')->count('metric')->get();

    Http::assertSent(
        fn (Request $request): bool =>
        $request->data()['from'] === '2024-01-01' &&
        $request->data()['to'] === '2024-01-31'
    );
});

it('normalises DateTimeInterface to Y-m-d when using between()', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->between(
        new DateTime('2024-01-01'),
        new DateTime('2024-01-31'),
    )->count('metric')->get();

    Http::assertSent(
        fn (Request $request): bool =>
        $request->data()['from'] === '2024-01-01' &&
        $request->data()['to'] === '2024-01-31'
    );
});

it('sends from and to when set individually', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->from('2024-01-01')->to('2024-01-31')->count('metric')->get();

    Http::assertSent(
        fn (Request $request): bool =>
        $request->data()['from'] === '2024-01-01' &&
        $request->data()['to'] === '2024-01-31'
    );
});

it('normalises DateTimeInterface to Y-m-d when using from() and to()', function (): void {
    fakeQueryResponse();

    new QueryBuilder()
        ->from(new DateTime('2024-06-15'))
        ->to(new DateTime('2024-07-15'))
        ->count('metric')
        ->get();

    Http::assertSent(
        fn (Request $request): bool =>
        $request->data()['from'] === '2024-06-15' &&
        $request->data()['to'] === '2024-07-15'
    );
});

it('sends interval daily when daily() is called', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->daily()->between('2024-01-01', '2024-01-07')->count('metric')->get();

    Http::assertSent(fn (Request $request): bool => $request->data()['interval'] === 'daily');
});

it('omits interval key when daily() is not called', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->count('metric')->get();

    Http::assertSent(fn (Request $request): bool => ! array_key_exists('interval', $request->data()));
});

it('omits from and to keys when not set', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->count('metric')->get();

    Http::assertSent(
        fn (Request $request): bool =>
        ! array_key_exists('from', $request->data()) &&
        ! array_key_exists('to', $request->data())
    );
});

it('sends multiple descriptors when count() is called multiple times', function (): void {
    fakeQueryResponse(['clicks' => 10, 'signups' => 5]);

    new QueryBuilder()->count('clicks')->count('signups')->get();

    Http::assertSent(fn (Request $request): bool => count($request->data()['data']) === 2);
});

it('sends has.events on the correct descriptor when withEvent() is called', function (): void {
    fakeQueryResponse();

    new QueryBuilder()
        ->count('clicks')
        ->withEvent(fn (EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('btn'))
        ->get();

    Http::assertSent(function (Request $request): bool {
        $descriptor = $request->data()['data'][0];

        return $descriptor['as'] === 'clicks'
            && $descriptor['has']['events'][0] === ['type' => 'clicked', 'identifier' => 'btn'];
    });
});

it('applies withEvent() to the last count() descriptor only', function (): void {
    fakeQueryResponse(['first' => 1, 'second' => 2]);

    new QueryBuilder()
        ->count('first')
        ->count('second')
        ->withEvent(fn (EventFilter $f): EventFilter => $f->type(EventType::CLICKED))
        ->get();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data()['data'];

        return ! array_key_exists('events', $data[0]['has'])
            && isset($data[1]['has']['events']);
    });
});

it('produces two event items when withEvent() is called twice on the same count', function (): void {
    fakeQueryResponse();

    new QueryBuilder()
        ->count('funnel')
        ->withEvent(fn (EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('start'))
        ->withEvent(fn (EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('end'))
        ->get();

    Http::assertSent(
        fn (Request $request): bool =>
        count($request->data()['data'][0]['has']['events']) === 2
    );
});

it('sends has as empty array when no withEvent() calls are made', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->count('all_events')->get();

    Http::assertSent(
        fn (Request $request): bool =>
        $request->data()['data'][0]['has'] === [] &&
        ! array_key_exists('events', $request->data()['data'][0]['has'])
    );
});

it('returns a QueryResponse instance from get()', function (): void {
    fakeQueryResponse(['signups' => 42]);

    $response = new QueryBuilder()->count('signups')->get();

    expect($response)->toBeInstanceOf(QueryResponse::class)
        ->and($response->get('signups'))->toBe(42);
});

it('configures the descriptor via closure when count() receives one', function (): void {
    fakeQueryResponse();

    new QueryBuilder()
        ->count(
            'clicks',
            fn (QueryDescriptor $d): QueryDescriptor => $d
                ->withEvent(fn (EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('btn'))
        )
        ->get();

    Http::assertSent(function (Request $request): bool {
        $descriptor = $request->data()['data'][0];

        return $descriptor['as'] === 'clicks'
            && $descriptor['has']['events'][0] === ['type' => 'clicked', 'identifier' => 'btn'];
    });
});

it('configures page and event filters via closure in the same count()', function (): void {
    fakeQueryResponse();

    new QueryBuilder()
        ->count(
            'home_clicks',
            fn (QueryDescriptor $d): QueryDescriptor => $d
                ->withPage(fn (PageFilter $f): PageFilter => $f->path('/home'))
                ->withEvent(fn (EventFilter $f): EventFilter => $f->type(EventType::CLICKED))
        )
        ->get();

    Http::assertSent(function (Request $request): bool {
        $descriptor = $request->data()['data'][0];

        return isset($descriptor['has']['pages'])
            && isset($descriptor['has']['events'])
            && $descriptor['has']['pages'][0] === ['path' => '/home']
            && $descriptor['has']['events'][0] === ['type' => 'clicked'];
    });
});

it('handles multiple count() calls each with their own closure', function (): void {
    fakeQueryResponse(['page_views' => 10, 'shares' => 3]);

    new QueryBuilder()
        ->count(
            'page_views',
            fn (QueryDescriptor $d): QueryDescriptor => $d
                ->withPage(fn (PageFilter $f): PageFilter => $f->path('/home'))
        )
        ->count(
            'shares',
            fn (QueryDescriptor $d): QueryDescriptor => $d
                ->withPage(fn (PageFilter $f): PageFilter => $f->path('/home'))
                ->withEvent(fn (EventFilter $f): EventFilter => $f->identifier('share-button'))
        )
        ->get();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data()['data'];

        return count($data) === 2
            && $data[0]['as'] === 'page_views'
            && ! array_key_exists('events', $data[0]['has'])
            && $data[1]['as'] === 'shares'
            && isset($data[1]['has']['events']);
    });
});

it('sends has.pages on the correct descriptor when withPage() is called', function (): void {
    fakeQueryResponse();

    new QueryBuilder()
        ->count('home_events')
        ->withPage(fn (PageFilter $f): PageFilter => $f->path('/home'))
        ->get();

    Http::assertSent(function (Request $request): bool {
        $descriptor = $request->data()['data'][0];

        return $descriptor['as'] === 'home_events'
            && $descriptor['has']['pages'][0] === ['path' => '/home'];
    });
});

it('applies withPage() to the last count() descriptor only', function (): void {
    fakeQueryResponse(['first' => 1, 'second' => 2]);

    new QueryBuilder()
        ->count('first')
        ->count('second')
        ->withPage(fn (PageFilter $f): PageFilter => $f->path('/home'))
        ->get();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data()['data'];

        return ! array_key_exists('pages', $data[0]['has'])
            && isset($data[1]['has']['pages']);
    });
});

it('sends the raw payload verbatim and returns a QueryResponse', function (): void {
    fakeQueryResponse(['my_metric' => 7]);

    $payload = [
        'type' => 'count',
        'data' => [['as' => 'my_metric', 'has' => []]],
    ];

    $response = new QueryBuilder()->raw($payload);

    Http::assertSent(fn (Request $request): bool => $request->data() === $payload);
    expect($response)->toBeInstanceOf(QueryResponse::class)
        ->and($response->get('my_metric'))->toBe(7);
});

it('is reachable through the facade and returns a QueryResponse', function (): void {
    fakeQueryResponse(['signups' => 42]);

    $response = JourneyTracker::query()
        ->count('signups')
        ->withEvent(fn (EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('signup'))
        ->get();

    expect($response)->toBeInstanceOf(QueryResponse::class)
        ->and($response->get('signups'))->toBe(42)
        ->and($response->signups)->toBe(42);

    Http::assertSent(
        fn (Request $request): bool => str_ends_with($request->url(), '/api/query')
            && $request->method() === 'POST'
            && $request->data()['type'] === 'count'
    );
});

it('hands back a fresh builder on every query() call', function (): void {
    expect(JourneyTracker::query())->not->toBe(JourneyTracker::query());
});

it('does not leak descriptors between two builders', function (): void {
    fakeQueryResponse();

    JourneyTracker::query()->count('first')->get();
    JourneyTracker::query()->count('second')->get();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data()['data'];

        return count($data) === 1;
    });
});

it('sends to without from when only to() is called', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->to('2024-01-31')->count('metric')->get();

    Http::assertSent(
        fn (Request $request): bool => $request->data()['to'] === '2024-01-31'
            && ! array_key_exists('from', $request->data())
    );
});

it('sends from without to when only from() is called', function (): void {
    fakeQueryResponse();

    new QueryBuilder()->from('2024-01-01')->count('metric')->get();

    Http::assertSent(
        fn (Request $request): bool => $request->data()['from'] === '2024-01-01'
            && ! array_key_exists('to', $request->data())
    );
});

it('returns the daily rows verbatim from a raw() call', function (): void {
    $rows = [
        ['date' => '2024-01-01', 'metric' => 5],
        ['date' => '2024-01-02', 'metric' => 3],
    ];

    fakeQueryResponse($rows);

    $response = new QueryBuilder()->raw(['type' => 'count', 'interval' => 'daily']);

    expect($response->all())->toBe($rows);
});

it('returns daily rows from get() when daily() is used', function (): void {
    $rows = [['date' => '2024-01-01', 'metric' => 5]];

    fakeQueryResponse($rows);

    $response = new QueryBuilder()->daily()->count('metric')->get();

    expect($response->all())->toBe($rows);
});

it('blows up when a filter is added before any count', function (string $method): void {
    $builder = new QueryBuilder();

    expect(fn (): QueryBuilder => $builder->{$method}(fn (object $filter): object => $filter))
        ->toThrow(LogicException::class, 'Add a count() to the query before filtering it with withEvent() or withPage().');
})->with(['withEvent', 'withPage']);
