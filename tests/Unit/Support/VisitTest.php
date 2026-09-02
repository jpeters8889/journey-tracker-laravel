<?php

declare(strict_types=1);

use Jpeters8889\JourneyTrackerLaravel\Support\Visit;

it('persists only the fields a later request needs', function (): void {
    $visit = new Visit('visit-abc', 1787577135, wasNew: true);

    expect($visit->toArray())->toBe([
        'id' => 'visit-abc',
        'seen' => 1787577135,
    ]);
});

it('hydrates a stored visit as one the client returned to us', function (): void {
    $visit = Visit::fromSession(['id' => 'visit-abc', 'seen' => 1787577135]);

    expect($visit)->not->toBeNull()
        ->and($visit->id)->toBe('visit-abc')
        ->and($visit->seen)->toBe(1787577135)
        ->and($visit->wasNew)->toBeFalse();
});

it('ignores extra keys in a stored visit', function (): void {
    $visit = Visit::fromSession(['id' => 'visit-abc', 'seen' => 1787577135, 'extra' => true]);

    expect($visit)->not->toBeNull()
        ->and($visit->id)->toBe('visit-abc');
});

it('refuses to hydrate anything it cannot read', function (mixed $stored): void {
    expect(Visit::fromSession($stored))->toBeNull();
})->with([
    'null' => [null],
    'a string' => ['visit-abc'],
    'an int' => [42],
    'no id' => [['seen' => 1787577135]],
    'no seen' => [['id' => 'visit-abc']],
    'id is not a string' => [['id' => 42, 'seen' => 1787577135]],
    'seen is not an epoch' => [['id' => 'visit-abc', 'seen' => 'yesterday']],
    'empty array' => [[]],
]);
