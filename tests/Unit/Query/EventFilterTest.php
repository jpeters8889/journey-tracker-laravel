<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\Enums\EventType;
use Jpeters8889\JourneyTrackerLaravel\Query\EventFilter;

it('returns an empty array when no fields are set', function (): void {
    expect((new EventFilter())->toArray())->toBeEmpty();
});

it('emits the enum string value when type is set via enum', function (): void {
    $filter = (new EventFilter())->type(EventType::CLICKED);

    expect($filter->toArray())->toBe(['type' => 'clicked']);
});

it('emits the string directly when type is set via string', function (): void {
    $filter = (new EventFilter())->type('typed');

    expect($filter->toArray())->toBe(['type' => 'typed']);
});

it('emits identifier when set', function (): void {
    $filter = (new EventFilter())->identifier('my-button');

    expect($filter->toArray())->toBe(['identifier' => 'my-button']);
});

it('emits parameters when set', function (): void {
    $filter = (new EventFilter())->withParameters(['plan' => 'pro']);

    expect($filter->toArray())->toBe(['parameters' => ['plan' => 'pro']]);
});

it('only includes set fields in toArray output', function (): void {
    $filter = (new EventFilter())->type(EventType::TYPED)->identifier('email-input');

    expect($filter->toArray())->toBe(['type' => 'typed', 'identifier' => 'email-input'])
        ->and($filter->toArray())->not->toHaveKey('parameters');
});

it('returns the same instance from each fluent method', function (): void {
    $filter = new EventFilter();

    expect($filter->type(EventType::CLICKED))->toBe($filter)
        ->and($filter->identifier('btn'))->toBe($filter)
        ->and($filter->withParameters([]))->toBe($filter);
});

it('supports all EventType enum cases', function (EventType $type): void {
    expect((new EventFilter())->type($type)->toArray()['type'])->toBe($type->value);
})->with(EventType::cases());
