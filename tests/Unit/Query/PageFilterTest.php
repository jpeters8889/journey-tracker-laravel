<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\Query\PageFilter;

it('returns an empty array when no fields are set', function (): void {
    expect(new PageFilter()->toArray())->toBeEmpty();
});

it('emits id when set', function (): void {
    $filter = new PageFilter()->id('550e8400-e29b-41d4-a716-446655440000');

    expect($filter->toArray())->toBe(['id' => '550e8400-e29b-41d4-a716-446655440000']);
});

it('emits path when set', function (): void {
    $filter = new PageFilter()->path('/home');

    expect($filter->toArray())->toBe(['path' => '/home']);
});

it('only includes set fields in toArray output', function (): void {
    $filter = new PageFilter()->path('/products/*');

    expect($filter->toArray())->toBe(['path' => '/products/*'])
        ->and($filter->toArray())->not->toHaveKey('id');
});

it('returns the same instance from each fluent method', function (): void {
    $filter = new PageFilter();

    expect($filter->id('some-id'))->toBe($filter)
        ->and($filter->path('/home'))->toBe($filter);
});
