<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;
use Jpeters8889\JourneyTrackerLaravel\Query\EventFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\PageFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryBuilder;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryResponse;

function fakeQueryResponse(array $data = ['metric' => 1]): void
{
    Http::fake([
        '*/api/query' => Http::response(['data' => $data]),
    ]);
}

it('sends type count in every payload', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())->count('metric')->get();

    Http::assertSent(fn(Request $request): bool => $request->data()['type'] === 'count');
});

it('sends from and to when set via between()', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())->between('2024-01-01', '2024-01-31')->count('metric')->get();

    Http::assertSent(fn(Request $request): bool =>
        $request->data()['from'] === '2024-01-01' &&
        $request->data()['to'] === '2024-01-31'
    );
});

it('normalises DateTimeInterface to Y-m-d when using between()', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())->between(
        new DateTime('2024-01-01'),
        new DateTime('2024-01-31'),
    )->count('metric')->get();

    Http::assertSent(fn(Request $request): bool =>
        $request->data()['from'] === '2024-01-01' &&
        $request->data()['to'] === '2024-01-31'
    );
});

it('sends from and to when set individually', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())->from('2024-01-01')->to('2024-01-31')->count('metric')->get();

    Http::assertSent(fn(Request $request): bool =>
        $request->data()['from'] === '2024-01-01' &&
        $request->data()['to'] === '2024-01-31'
    );
});

it('normalises DateTimeInterface to Y-m-d when using from() and to()', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())
        ->from(new DateTime('2024-06-15'))
        ->to(new DateTime('2024-07-15'))
        ->count('metric')
        ->get();

    Http::assertSent(fn(Request $request): bool =>
        $request->data()['from'] === '2024-06-15' &&
        $request->data()['to'] === '2024-07-15'
    );
});

it('sends interval daily when daily() is called', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())->daily()->between('2024-01-01', '2024-01-07')->count('metric')->get();

    Http::assertSent(fn(Request $request): bool => $request->data()['interval'] === 'daily');
});

it('omits interval key when daily() is not called', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())->count('metric')->get();

    Http::assertSent(fn(Request $request): bool => ! array_key_exists('interval', $request->data()));
});

it('omits from and to keys when not set', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())->count('metric')->get();

    Http::assertSent(fn(Request $request): bool =>
        ! array_key_exists('from', $request->data()) &&
        ! array_key_exists('to', $request->data())
    );
});

it('sends multiple descriptors when count() is called multiple times', function (): void {
    fakeQueryResponse(['clicks' => 10, 'signups' => 5]);

    (new QueryBuilder())->count('clicks')->count('signups')->get();

    Http::assertSent(fn(Request $request): bool => count($request->data()['data']) === 2);
});

it('sends has.events on the correct descriptor when withEvent() is called', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())
        ->count('clicks')
        ->withEvent(fn(EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('btn'))
        ->get();

    Http::assertSent(function (Request $request): bool {
        $descriptor = $request->data()['data'][0];

        return $descriptor['as'] === 'clicks'
            && $descriptor['has']['events'][0] === ['type' => 'clicked', 'identifier' => 'btn'];
    });
});

it('applies withEvent() to the last count() descriptor only', function (): void {
    fakeQueryResponse(['first' => 1, 'second' => 2]);

    (new QueryBuilder())
        ->count('first')
        ->count('second')
        ->withEvent(fn(EventFilter $f): EventFilter => $f->type(EventType::CLICKED))
        ->get();

    Http::assertSent(function (Request $request): bool {
        $data = $request->data()['data'];

        return ! array_key_exists('events', $data[0]['has'])
            && isset($data[1]['has']['events']);
    });
});

it('produces two event items when withEvent() is called twice on the same count', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())
        ->count('funnel')
        ->withEvent(fn(EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('start'))
        ->withEvent(fn(EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('end'))
        ->get();

    Http::assertSent(fn(Request $request): bool =>
        count($request->data()['data'][0]['has']['events']) === 2
    );
});

it('sends has as empty array when no withEvent() calls are made', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())->count('all_events')->get();

    Http::assertSent(fn(Request $request): bool =>
        $request->data()['data'][0]['has'] === [] &&
        ! array_key_exists('events', $request->data()['data'][0]['has'])
    );
});

it('returns a QueryResponse instance from get()', function (): void {
    fakeQueryResponse(['signups' => 42]);

    $response = (new QueryBuilder())->count('signups')->get();

    expect($response)->toBeInstanceOf(QueryResponse::class)
        ->and($response->get('signups'))->toBe(42);
});

it('sends has.pages on the correct descriptor when withPage() is called', function (): void {
    fakeQueryResponse();

    (new QueryBuilder())
        ->count('home_events')
        ->withPage(fn(PageFilter $f): PageFilter => $f->path('/home'))
        ->get();

    Http::assertSent(function (Request $request): bool {
        $descriptor = $request->data()['data'][0];

        return $descriptor['as'] === 'home_events'
            && $descriptor['has']['pages'][0] === ['path' => '/home'];
    });
});

it('applies withPage() to the last count() descriptor only', function (): void {
    fakeQueryResponse(['first' => 1, 'second' => 2]);

    (new QueryBuilder())
        ->count('first')
        ->count('second')
        ->withPage(fn(PageFilter $f): PageFilter => $f->path('/home'))
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

    $response = (new QueryBuilder())->raw($payload);

    Http::assertSent(fn(Request $request): bool => $request->data() === $payload);
    expect($response)->toBeInstanceOf(QueryResponse::class)
        ->and($response->get('my_metric'))->toBe(7);
});
