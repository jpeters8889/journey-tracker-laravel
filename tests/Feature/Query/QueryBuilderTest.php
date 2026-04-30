<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;
use Jpeters8889\JourneyTrackerLaravel\Facades\JourneyTracker;
use Jpeters8889\JourneyTrackerLaravel\Query\EventFilter;
use Jpeters8889\JourneyTrackerLaravel\Query\QueryResponse;

it('can be called via the facade and returns a QueryResponse', function (): void {
    Http::fake(['*/api/query' => Http::response(['data' => ['signups' => 42]])]);

    $response = JourneyTracker::query()
        ->count('signups')
        ->withEvent(fn(EventFilter $f): EventFilter => $f->type(EventType::CLICKED)->identifier('signup'))
        ->get();

    expect($response)->toBeInstanceOf(QueryResponse::class)
        ->and($response->get('signups'))->toBe(42)
        ->and($response->signups)->toBe(42);

    Http::assertSent(fn(Request $request): bool =>
        str_ends_with($request->url(), '/api/query') &&
        $request->method() === 'POST' &&
        $request->data()['type'] === 'count'
    );
});
