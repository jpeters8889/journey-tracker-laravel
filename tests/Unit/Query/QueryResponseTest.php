<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\Query\QueryResponse;

it('returns the value for a given alias via get()', function (): void {
    $response = new QueryResponse(['signups' => 42]);

    expect($response->get('signups'))->toBe(42);
});

it('returns the same value via magic __get', function (): void {
    $response = new QueryResponse(['signups' => 42]);

    expect($response->signups)->toBe(42);
});

it('returns the full data array via all()', function (): void {
    $data = ['signups' => 42, 'clicks' => 100];
    $response = new QueryResponse($data);

    expect($response->all())->toBe($data);
});

it('returns daily breakdown via all() as date-keyed rows', function (): void {
    $daily = [
        ['date' => '2024-01-01', 'clicks' => 5, 'views' => 10],
        ['date' => '2024-01-02', 'clicks' => 3, 'views' => 7],
    ];
    $response = new QueryResponse($daily);

    expect($response->all())->toBe($daily);
});

it('blows up when asked for an alias it does not carry', function (): void {
    $response = new QueryResponse(['signups' => 42]);

    expect(fn (): int => $response->get('typo'))->toThrow(ErrorException::class);
});

it('blows up when an alias is read off a daily response', function (): void {
    $response = new QueryResponse([
        ['date' => '2024-01-01', 'signups' => 5],
    ]);

    expect(fn (): int => $response->get('signups'))->toThrow(ErrorException::class);
});

it('still exposes daily rows through all()', function (): void {
    $rows = [['date' => '2024-01-01', 'signups' => 5]];

    expect(new QueryResponse($rows)->all())->toBe($rows);
});
